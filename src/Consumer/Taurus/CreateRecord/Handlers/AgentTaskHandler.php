<?php

namespace Taurus\Workflow\Consumer\Taurus\CreateRecord\Handlers;

use Avatar\Infrastructure\Models\Api\v1\LexisNexisErrorModel;
use Avatar\Infrastructure\Models\Api\v1\PolicyNoteModel;
use Avatar\Infrastructure\Models\Api\v1\TbAgentTasksMaster;
use Avatar\Infrastructure\Models\Api\v1\TbAgentTasksMasterMapping;
use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Avatar\Infrastructure\Models\Api\v1\TbPotransaction;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Taurus\Workflow\Consumer\Common\CreateRecord\CreateRecordHandlerInterface;
use Taurus\Workflow\Events\DispatchCustomEvents;

/**
 * Creates an agent task from a CREATE_RECORD action.
 *
 * Expected (resolved) payload keys:
 *   policyMasterId       - tb_policies.n_PolicyNoId_PK (required)
 *   taskId               - tb_agent_tasks_list id (required)
 *   taskTitle            - title of the task (required)
 *   note                 - note for the task
 *   policyTransactionId  - tb_potransactions PK, falls back to the policy's latest transaction
 *   agentId / agencyId   - optional, fall back to the policy's agent / agency
 *
 * Mirrors gfs-saas-policy AgentTasksService::saveAgentTasks(): the task is
 * added under the policy's existing agent task master when there is one,
 * otherwise a new master is created for it.
 */
class AgentTaskHandler implements CreateRecordHandlerInterface
{
    public function execute(array $payload, array $context): mixed
    {
        $policyPK = (int) ($payload['policyMasterId'] ?? 0);
        $taskId = (int) ($payload['taskId'] ?? 0);
        $taskTitle = trim((string) ($payload['taskTitle'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));

        if (empty($policyPK) || empty($taskId) || $taskTitle === '') {
            throw new \InvalidArgumentException('policyMasterId, taskId and taskTitle are required to create an agent task.');
        }

        $policy = TbPolicy::where('n_PolicyNoId_PK', $policyPK)->first();

        if (! $policy) {
            throw new \InvalidArgumentException("Policy {$policyPK} does not exist.");
        }

        $transactionPK = $this->resolveTransactionPK($payload, $policyPK);

        ['mappingId' => $mappingId, 'created' => $created, 'masterNote' => $masterNote] = $this->saveAgentTask($payload, $policy, $transactionPK, $taskId, $taskTitle, $note);

        $this->linkLexisNexisError($context, $mappingId, $created);

        // A new task is always noted on the policy; an already open one only when a note was appended.
        // The policy note carries exactly the note saved on the agent task master (the combined one when appended).
        if ($created || $note !== '') {
            $this->addPolicyNote($policyPK, $transactionPK, $taskTitle, $masterNote);
        }

        if (! $created) {
            Log::warning('WORKFLOW - Agent task already open for policy, appended note to the master', [
                'workflowId' => $context['workflowId'] ?? null,
                'policyPK' => $policyPK,
                'taskId' => $taskId,
                'mappingId' => $mappingId,
            ]);

            return null;
        }

        $this->fireMappingCreatedEvent($mappingId);

        Log::info('WORKFLOW - Agent task created', [
            'workflowId' => $context['workflowId'] ?? null,
            'policyPK' => $policyPK,
            'mappingId' => $mappingId,
        ]);

        return $mappingId;
    }

    /**
     * Adds the task under the policy's existing agent task master, or under a
     * new master when the policy has none yet.
     *
     * @return array{mappingId: int, created: bool, masterNote: string} The new mapping, or the already open one
     *                                                                  (created = false, the note is then appended to the master's note),
     *                                                                  with the note as saved on the master.
     */
    private function saveAgentTask(array $payload, TbPolicy $policy, int $transactionPK, int $taskId, string $taskTitle, string $note): array
    {
        $policyPK = (int) $policy->n_PolicyNoId_PK;
        $userId = user()->Admin_ID ?? Config::get('workflow.default_system_user_id');
        $nowDate = date('Y-m-d H:i:s');

        return DB::transaction(function () use ($payload, $policy, $policyPK, $transactionPK, $taskId, $taskTitle, $note, $userId, $nowDate) {
            $masterId = TbAgentTasksMaster::where('policymaster_FK', $policyPK)->orderByDesc('id')->value('id');

            if ($masterId) {
                // A new open task re-opens the master.
                TbAgentTasksMaster::where('id', $masterId)->update(['complete_status' => 'N']);

                $openMappingId = TbAgentTasksMasterMapping::where('agent_tasks_masters_id', $masterId)
                    ->where('task_id', $taskId)
                    ->where('is_deleted', 'N')
                    ->where('complete_status', 'N')
                    ->value('id');

                // The same task is already open for the policy, don't add it twice,
                // only append the new note to the master's note.
                if ($openMappingId) {
                    $masterNote = $note;

                    if ($note !== '') {
                        $existingNote = (string) TbAgentTasksMaster::where('id', $masterId)->value('note');
                        $masterNote = $existingNote === '' ? $note : $existingNote."\n".$note;

                        TbAgentTasksMaster::where('id', $masterId)->update([
                            'note' => $masterNote,
                            'updated_by' => $userId,
                            'updated_at' => $nowDate,
                        ]);
                    }

                    return ['mappingId' => (int) $openMappingId, 'created' => false, 'masterNote' => $masterNote];
                }
            } else {
                $masterId = TbAgentTasksMaster::insertGetId([
                    'note' => $note,
                    'agency_id' => $this->valueOrNull($payload['agencyId'] ?? null) ?? $policy->n_AgencyAccount_FK,
                    'agent_id' => $this->valueOrNull($payload['agentId'] ?? null) ?? $policy->n_SubAgentPersoninfo_FK,
                    'potransaction_FK' => $transactionPK,
                    'policymaster_FK' => $policyPK,
                ]);
            }

            $mappingId = TbAgentTasksMasterMapping::insertGetId([
                'agent_tasks_masters_id' => $masterId,
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'complete_status' => 'N',
                'created_by' => $userId,
                'created_at' => $nowDate,
            ]);

            return ['mappingId' => (int) $mappingId, 'created' => true, 'masterNote' => $note];
        });
    }

    /**
     * For workflows on the LexisNexis error module, stores the agent task mapping on
     * the tb_lexisnexis_errors row the task was raised for. A newly created mapping
     * is always stored; an already open one only fills a row that isn't linked yet.
     */
    private function linkLexisNexisError(array $context, int $mappingId, bool $created): void
    {
        if (class_basename((string) ($context['workflowModule'] ?? '')) !== class_basename(LexisNexisErrorModel::class)) {
            return;
        }

        $errorId = (int) ($context['sourceRecord']['Id'] ?? 0);

        if (! $errorId) {
            Log::warning('WORKFLOW - LexisNexis error id missing, agent task mapping not linked', [
                'workflowId' => $context['workflowId'] ?? null,
                'mappingId' => $mappingId,
            ]);

            return;
        }

        LexisNexisErrorModel::where('id', $errorId)
            ->when(! $created, fn ($query) => $query->whereNull('agent_task_master_mapping_id'))
            ->update(['agent_task_master_mapping_id' => $mappingId]);
    }

    /**
     * The payload's transaction when it has one, otherwise the policy's latest transaction.
     */
    private function resolveTransactionPK(array $payload, int $policyPK): int
    {
        $transactionPK = (int) ($payload['policyTransactionId'] ?? 0);

        if ($transactionPK) {
            $belongsToPolicy = TbPotransaction::where('n_potransaction_PK', $transactionPK)
                ->where('n_PolicyMaster_FK', $policyPK)
                ->exists();

            if (! $belongsToPolicy) {
                throw new \InvalidArgumentException("Transaction {$transactionPK} does not belong to policy {$policyPK}.");
            }

            return $transactionPK;
        }

        return (int) TbPotransaction::where('n_PolicyMaster_FK', $policyPK)
            ->orderByDesc('n_potransaction_PK')
            ->value('n_potransaction_PK');
    }

    /**
     * Treats an unresolved / empty placeholder as missing.
     */
    private function valueOrNull($value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * AppServiceProvider::boot() does not register the ModelObserver for
     * TbAgentTasksMasterMapping, so the CREATE event is fired manually.
     */
    private function fireMappingCreatedEvent(int $mappingId): void
    {
        try {
            DispatchCustomEvents::fireEvent(
                'CREATE',
                TbAgentTasksMasterMapping::class,
                [],
                $mappingId
            );
        } catch (\Exception $e) {
            Log::error('WORKFLOW - DISPATCH CUSTOM EVENTS - Error in firing event', [
                'agent_task_master_mapping_id' => $mappingId,
                'eventDescription' => 'Agent Task CREATE Event',
                'errorMessage' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function addPolicyNote(int $policyPK, int $transactionPK, string $taskTitle, string $note): void
    {
        (new PolicyNoteModel)->saveOrUpdateNote([
            'note' => 'Agent task : '.$taskTitle.'. UW Note: '.$note,
            'isHidden' => 'N',
            'flag' => 'SYSTEM',
            'policyPK' => $policyPK,
            'poTransPK' => $transactionPK,
        ]);
    }
}

<?php

namespace Taurus\Workflow\Consumer\Taurus\CreateRecord\Handlers;

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

        $mappingId = $this->saveAgentTask($payload, $policy, $transactionPK, $taskId, $taskTitle, $note);

        if (! $mappingId) {
            Log::warning('WORKFLOW - Agent task already open for policy, skipping', [
                'workflowId' => $context['workflowId'] ?? null,
                'policyPK' => $policyPK,
                'taskId' => $taskId,
            ]);

            return null;
        }

        $this->fireMappingCreatedEvent($mappingId);
        $this->addPolicyNote($policyPK, $transactionPK, $taskTitle, $note);

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
     * @return int|null The new mapping id, or null when the task is already open for the policy.
     */
    private function saveAgentTask(array $payload, TbPolicy $policy, int $transactionPK, int $taskId, string $taskTitle, string $note): ?int
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

                // The same task is already open for the policy, don't add it twice.
                if ($openMappingId) {
                    return null;
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

            return TbAgentTasksMasterMapping::insertGetId([
                'agent_tasks_masters_id' => $masterId,
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'complete_status' => 'N',
                'created_by' => $userId,
                'created_at' => $nowDate,
            ]);
        });
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

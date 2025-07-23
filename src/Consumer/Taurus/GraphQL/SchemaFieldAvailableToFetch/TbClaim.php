<?php

namespace Taurus\Workflow\Consumer\Taurus\GraphQL\SchemaFieldAvailableToFetch;

class TbClaim
{
  /**
   * @var array $fieldMapping
   * 
   * This property holds the mapping of fields that are available to fetch.
   * It is an associative array where keys represent PLACEHOLDER and values
   * represent the corresponding data or configuration for those fields.
   */
  protected $fieldMapping = [];
  /**
   * @var string|null $queryName The name of the query associated with this class.
   */
  protected $queryName;

  public function __construct()
  {
    $this->fieldMapping = $this->initializeFieldMapping();
    $this->queryName = 'claim';
  }

  /**
   * Retrieves the field mapping with GraphQL schema for the TbClaim.
   *
   * This method returns an associative array that maps the fields
   * of the TbClaim to their corresponding values or attributes.
   *
   * @return array An associative array representing the field mapping.
   */
  public function getFieldMapping()
  {
    return $this->fieldMapping;
  }

  /**
   * Retrieves the query name for the TbClaim.
   *
   * This method returns the name of the GraphQL query that can be used
   * to fetch data related to the TbClaim.
   *
   * @return string The name of the GraphQL query for TbClaim.
   */
  public function getQueryName()
  {
    return $this->queryName;
  }


  /**
   * Initializes the field mapping with GraphQL schema for the TbClaim class.
   *
   * This method sets up the mapping of fields that can be fetched
   * from the GraphQL schema. It is called during the initialization
   * phase of the class to ensure that all fields are properly mapped
   * before any operations are performed.
   * 
   * KEYS are PLACEHOLDER for the GraphQL schema to be replaced.
   *
   * @return array
   */
  private function initializeFieldMapping()
  {
    $fieldMapping = [
      'claimId' => [
        'GraphQLschemaToReplace' => [
          'claimId' => null
        ],
        'jqFilter' => '.claim.claimId'
      ],
      'PolicyNumber' => [
        'GraphQLschemaToReplace' => [
          'riskId' => null
        ],
        'jqFilter' => '.claim.riskId'
      ],
      'policyId' => [
        'GraphQLschemaToReplace' => [
          'policyId' => null
        ],
        'jqFilter' => '.claim.policyId'
      ],
      'insuredName' => [
        'GraphQLschemaToReplace' => [
          'insuredName' => null
        ],
        'jqFilter' => '.claim.insuredName'
      ],
      'insuredEmail' => [
        'GraphQLschemaToReplace' => [
          'claimCommunication' => [
            'isAcceptEmail' => null,
            'primaryEmail' => null,
            'secondaryEmail' => null
          ]
        ],
        'jqFilter' => '.claim.claimCommunication',
        'parseResultCallback' => 'parseClaimCommunication'
      ],
    ];

    $fieldMapping['insuredPropertyAddress'] = [
      'GraphQLschemaToReplace' => [
        'insuredPerson' => [
          'TbPersonaddress' => [
            'addressTypeCode' => null,
            'houseNo' => null,
            'streetName' => null,
            'addressLine1' => null,
            'addressLine2' => null,
            'addressLine3' => null,
            'addressLine4' => null,
            'postalCode' => null,
            'postalCodeSuffix' => null,
            'city' => null,
            'state' => null,
            'isDefaultAddress' => null,
          ]
        ]
      ],
      'jqFilter' => '.claim.insuredPerson.TbPersonaddress[] | select(.isDefaultAddress == "Y" and .addressTypeCode == "Location")',
      'parseResultCallback' => 'parsePropertyAddress'
    ];


    $fieldMapping['insuredMailingAddress'] = [
      'GraphQLschemaToReplace' => $fieldMapping['insuredPropertyAddress']['GraphQLschemaToReplace'],
      'jqFilter' => '.claim.insuredPerson.TbPersonaddress[] | select(.addressTypeCode == "Mailing")',
      'parseResultCallback' => 'parseMailingAddress'
    ];


    $fieldMapping['adjustingFirmAddress'] = [
      'GraphQLschemaToReplace' => [
        'adjustingFirm' => [
          'personInfo' => [
            'TbPersonaddress' => [
              'addressTypeCode' => null,
              'houseNo' => null,
              'streetName' => null,
              'addressLine1' => null,
              'addressLine2' => null,
              'addressLine3' => null,
              'addressLine4' => null,
              'postalCode' => null,
              'postalCodeSuffix' => null,
              'city' => null,
              'state' => null,
              'isDefaultAddress' => null,
            ]
          ]
        ]
      ],
      'jqFilter' => '.claim.adjustingFirm[].personInfo.TbPersonaddress[] | select(.addressTypeCode == "Mailing")',
      'parseResultCallback' => 'parseAdjustingFirmAddress'
    ];

    $fieldMapping['adjustingFirmEmail'] = [
      'GraphQLschemaToReplace' => [
        'adjustingFirm' => [
          'personInfo' => [
            'emailInfo' => [
              'email' => null,
              'isDefault' => null,
            ]
          ]
        ]
      ],
      'jqFilter' => '[.claim.adjustingFirm[].personInfo.emailInfo[] | select(.isDefault == "Y")]',
      'parseResultCallback' => 'parseAdjustingFirmEmail'
    ];

    $fieldMapping['adjustingFirmPhone'] = [
      'GraphQLschemaToReplace' => [
        'adjustingFirm' => [
          'personInfo' => [
            'phoneInfo' => [
              'phoneNumber' => null,
              'isDefault' => null,
            ]
          ]
        ]
      ],
      'jqFilter' => '[.claim.adjustingFirm[].personInfo.phoneInfo[] | select(.isDefault == "Y")]',
      'parseResultCallback' => 'parseAdjustingFirmPhone'
    ];

    $fieldMapping['adjustingFirmPhone'] = [
      'GraphQLschemaToReplace' => [
        'adjustingFirm' => [
          'personInfo' => [
            'phoneInfo' => [
              'phoneNumber' => null,
              'isDefault' => null,
            ]
          ]
        ]
      ],
      'jqFilter' => '[.claim.adjustingFirm[].personInfo.phoneInfo[] | select(.isDefault == "Y")]',
      'parseResultCallback' => 'parseAdjustingFirmPhone'
    ];

    return $fieldMapping;
  }


  private function parseAddress($addressArr)
  {
    if (empty($addressArr)) {
      return null;
    }

    $address = [
      'addressLine1' => ($addressArr['houseNo'] ?? "") . ' ' . ($addressArr['streetName'] ?? ($addressArr['addressLine1'] ?? "")),
      'city' => $addressArr['city'] ?? null,
      'state' => $addressArr['state'] ?? null,
      'postalCode' => $addressArr['postalCode'] ?? null,
    ];

    $address = array_filter(array_map('trim', $address), function ($item) {
      return !empty($item);
    });

    return implode(', ', $address);
  }

  public function parseAdjustingFirmAddress($addressArr)
  {
    return $this->parseAddress($addressArr);
  }

  public function parseMailingAddress($addressArr)
  {
    return $this->parseAddress($addressArr);
  }

  public function parsePropertyAddress($addressArr)
  {
    return $this->parseAddress($addressArr);
  }

  public function parseAdjustingFirmEmail($emailArr)
  {
    return $emailArr[0]['email'] ?? null;
  }

  public function parseAdjustingFirmPhone($phoneArr)
  {
    return $phoneArr[0]['phoneNumber'] ?? null;
  }

  public function parseClaimCommunication($claimCommunication)
  {
    if (empty($claimCommunication)) {
      return null;
    }

    $email = $claimCommunication['primaryEmail'] ?? null;
    if (empty($email)) {
      $email = $claimCommunication['secondaryEmail'] ?? null;
    }

    return $email;
  }
}

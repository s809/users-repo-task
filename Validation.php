<?php

use Laminas\Diactoros\ServerRequest;
use Valitron\Validator;

Validator::addRule('filter_var', function ($field, $value, array $params) {
    return filter_var($value, $params[0]) !== false;
}, 'has an invalid type');

class Validation {
    private array $userFields;
    private array $validatorRules;
    private array $validatorRulesAllRequired;

    public function __construct() {
        $this->userFields = ['full_name', 'role', 'efficiency'];

        $this->validatorRules = [
            "lengthBetween" => [
                ["full_name", 1, 50],
                ["role", 1, 50]
            ],
            "filter_var" => [
                ["efficiency", FILTER_VALIDATE_INT]
            ]
        ];

        $this->validatorRulesAllRequired = [
            ...$this->validatorRules,
            "required" => $this->userFields
        ];
    }

    public function getJsonData(ServerRequest $request) {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException("Invalid or missing JSON body");
        }

        return $data;
    }

    public function filterUserFields(array $data, bool $requireAtLeastOneKey) {
        $fields = array_intersect_key(
            $data,
            array_flip($this->userFields)
        );

        if (empty($fields) && $requireAtLeastOneKey) {
            throw new InvalidArgumentException("At least one key must be present: " . implode(", ", $this->userFields));
        }

        return $fields;
    }

    public function validateUserFields(array $data, bool $allRequired)
    {
        $validator = new Validator($data);
        $validator->rules($allRequired
            ? $this->validatorRulesAllRequired
            : $this->validatorRules);

        if (!$validator->validate()) {
            throw new InvalidArgumentException(
                implode("\n", array_map(
                    fn($fieldErrors) => implode("\n", $fieldErrors),
                    $validator->errors()
                ))
            );
        }
    }

    public function filterAndValidateUserData(array $data, bool $allRequired){
        $data = $this->filterUserFields($data, true);
        $this->validateUserFields($data, $allRequired);
        return $data;
    }
}


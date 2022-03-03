<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Validator;

interface ValidatorInterface
{
    public function setSchema($schema, $additionalProperties = []);
    public function validate(string $msg);
    public function getContentType();
}

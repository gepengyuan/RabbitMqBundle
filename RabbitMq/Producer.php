<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use OldSound\RabbitMqBundle\RabbitMq\JsonValidator;
use OldSound\RabbitMqBundle\RabbitMq\XmlValidator;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;
    protected $defaultRoutingKey = '';
    public $validatorCheck = false;
    public $validatorFile = "";

    public function setValidatorFile($validatorFile){
        $this->validatorFile = $validatorFile;
    }

    public function setValidatorCheck($validatorCheck)
    {
        $this->validatorCheck = $validatorCheck;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function setDeliveryMode($deliveryMode)
    {
        $this->deliveryMode = $deliveryMode;

        return $this;
    }

    public function setDefaultRoutingKey($defaultRoutingKey)
    {
        $this->defaultRoutingKey = $defaultRoutingKey;

        return $this;
    }

    protected function getBasicProperties()
    {
        return array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode);
    }

    public function validateMessage($msg)
    {
        if (!array_key_exists($this->contentType, $this->validatorFile)){
            throw new Exception('Cannot find validator file of ' . $this->contentType);
        }
        // Insert new validator here
        if ($this->contentType == 'application/json'){
            $validatorEngine = new JsonValidator();
        }

        if ($this->contentType == 'application/xml'){
            $validatorEngine = new XmlValidator();
        }
        
        if (!$validatorEngine->isValid($msg, $this->validatorFile[$this->contentType])){
            throw new Exception($this->contentType . " message verification failed");
        }

    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = null, $additionalProperties = array(), array $headers = null)
    {
        if ($this->validatorCheck){
            $this->validateMessage($msgBody);
        }

        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage((string) $msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $real_routingKey = $routingKey !== null ? $routingKey : $this->defaultRoutingKey;
        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string)$real_routingKey);
        $this->logger->debug('AMQP message published', array(
            'amqp' => array(
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers
            )
        ));
    }
}

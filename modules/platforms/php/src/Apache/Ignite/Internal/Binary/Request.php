<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apache\Ignite\Internal\Binary;

use Apache\Ignite\Type\ObjectType;

class Request
{
    private $id;
    private $opCode;
    private $payloadWriter;
    private $payloadReader;
    private $isHandshake;
    
    private static $requestId = 0;
            
    public function __construct(int $opCode, ?callable $payloadWriter, callable $payloadReader = null, bool $isHandshake = false)
    {
        $this->id = Request::getRequestId();
        $this->opCode = $opCode;
        $this->payloadWriter = $payloadWriter;
        $this->payloadReader = $payloadReader;
        $this->isHandshake = $isHandshake;
    }
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function isHandshake(): bool
    {
        return $this->isHandshake;
    }

    public function getMessage(): MessageBuffer
    {
        $message = new MessageBuffer();
        // Skip message length
        $messageStartPos = BinaryUtils::getSize(ObjectType::INTEGER);
        $message->setPosition($messageStartPos);
        if ($this->opCode >= 0) {
            // Op code
            $message->writeShort($this->opCode);
            // Request id
            $message->writeLong($this->id);
        }
        if ($this->payloadWriter !== null) {
            // Payload
            call_user_func($this->payloadWriter, $message);
        }
        // Message length
        $message->setPosition(0);
        $message->writeInteger($message->getLength() - $messageStartPos);
        return $message;
    }
    
    public function getPayloadReader(): ?callable
    {
        return $this->payloadReader;
    }
    
    private static function getRequestId(): int
    {
        $result = Request::$requestId;
        Request::$requestId++;
        return $result;
    }
}

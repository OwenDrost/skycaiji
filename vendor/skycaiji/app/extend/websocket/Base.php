<?php
/*
 |--------------------------------------------------------------------------
 | SkyCaiji (蓝天采集器)
 |--------------------------------------------------------------------------
 | Copyright (c) 2018 https://www.skycaiji.com All rights reserved.
 |--------------------------------------------------------------------------
 | 使用协议  https://www.skycaiji.com/licenses
 |--------------------------------------------------------------------------
 */


/**
 * Copyright (C) 2014-2020 Textalk/Abicart and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/Textalk/websocket-php/master/COPYING
 */

namespace WebSocket;

class Base
{
    protected $socket;
    protected $options = [];
    protected $is_closing = false;
    protected $last_opcode = null;
    protected $close_status = null;

    protected static $opcodes = array(
        'continuation' => 0,
        'text'         => 1,
        'binary'       => 2,
        'close'        => 8,
        'ping'         => 9,
        'pong'         => 10,
    );

    public function getLastOpcode()
    {
        return $this->last_opcode;
    }

    public function getCloseStatus()
    {
        return $this->close_status;
    }

    public function isConnected()
    {
        return $this->socket && get_resource_type($this->socket) == 'stream';
    }

    public function setTimeout($timeout)
    {
        $this->options['timeout'] = $timeout;

        if ($this->isConnected()) {
            stream_set_timeout($this->socket, $timeout);
        }
    }

    public function setFragmentSize($fragment_size)
    {
        $this->options['fragment_size'] = $fragment_size;
        return $this;
    }

    public function getFragmentSize()
    {
        return $this->options['fragment_size'];
    }

    public function send($payload, $opcode = 'text', $masked = true)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!in_array($opcode, array_keys(self::$opcodes))) {
            throw new BadOpcodeException("Bad opcode '$opcode'.  Try 'text' or 'binary'.");
        }

        $payload_chunks = str_split($payload, $this->options['fragment_size']);

        for ($index = 0; $index < count($payload_chunks); ++$index) {
            $chunk = $payload_chunks[$index];
            $final = $index == count($payload_chunks) - 1;

            $this->sendFragment($final, $chunk, $opcode, $masked);

            
            $opcode = 'continuation';
        }
    }

    protected function sendFragment($final, $payload, $opcode, $masked)
    {
        
        $frame_head_binstr = '';

        
        $frame_head_binstr .= (bool) $final ? '1' : '0';

        
        $frame_head_binstr .= '000';

        
        $frame_head_binstr .= sprintf('%04b', self::$opcodes[$opcode]);

        
        $frame_head_binstr .= $masked ? '1' : '0';

        
        $payload_length = strlen($payload);
        if ($payload_length > 65535) {
            $frame_head_binstr .= decbin(127);
            $frame_head_binstr .= sprintf('%064b', $payload_length);
        } elseif ($payload_length > 125) {
            $frame_head_binstr .= decbin(126);
            $frame_head_binstr .= sprintf('%016b', $payload_length);
        } else {
            $frame_head_binstr .= sprintf('%07b', $payload_length);
        }

        $frame = '';

        
        foreach (str_split($frame_head_binstr, 8) as $binstr) {
            $frame .= chr(bindec($binstr));
        }

        
        if ($masked) {
            
            $mask = '';
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(rand(0, 255));
            }
            $frame .= $mask;
        }

        
        for ($i = 0; $i < $payload_length; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        $this->write($frame);
    }

    public function receive()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $payload = '';
        do {
            $response = $this->receiveFragment();
            $payload .= $response[0];
        } while (!$response[1]);

        return $payload;
    }

    protected function receiveFragment()
    {
        
        $data = $this->read(2);

        
        $final = (bool) (ord($data[0]) & 1 << 7);

        
        $rsv1  = (bool) (ord($data[0]) & 1 << 6);
        $rsv2  = (bool) (ord($data[0]) & 1 << 5);
        $rsv3  = (bool) (ord($data[0]) & 1 << 4);

        
        $opcode_int = ord($data[0]) & 31; 
        $opcode_ints = array_flip(self::$opcodes);
        if (!array_key_exists($opcode_int, $opcode_ints)) {
            throw new ConnectionException(
                "Bad opcode in websocket frame: $opcode_int",
                ConnectionException::BAD_OPCODE
            );
        }
        $opcode = $opcode_ints[$opcode_int];

        
        if ($opcode !== 'continuation') {
            $this->last_opcode = $opcode;
        }

        
        $mask = (bool) (ord($data[1]) >> 7);  

        $payload = '';

        
        $payload_length = (int) ord($data[1]) & 127; 
        if ($payload_length > 125) {
            if ($payload_length === 126) {
                $data = $this->read(2); 
            } else {
                $data = $this->read(8); 
            }
            $payload_length = bindec(self::sprintB($data));
        }

        
        if ($mask) {
            $masking_key = $this->read(4);
        }

        
        if ($payload_length > 0) {
            $data = $this->read($payload_length);

            if ($mask) {
                
                for ($i = 0; $i < $payload_length; $i++) {
                    $payload .= ($data[$i] ^ $masking_key[$i % 4]);
                }
            } else {
                $payload = $data;
            }
        }

        
        if ($opcode === 'ping') {
            $this->send($payload, 'pong', true);
        }

        if ($opcode === 'close') {
            
            if ($payload_length > 0) {
                $status_bin = $payload[0] . $payload[1];
                $status = bindec(sprintf("%08b%08b", ord($payload[0]), ord($payload[1])));
                $this->close_status = $status;
            }
            
            if ($payload_length >= 2) {
                $payload = substr($payload, 2);
            }

            if ($this->is_closing) {
                $this->is_closing = false; 
            } else {
                $this->send($status_bin . 'Close acknowledged: ' . $status, 'close', true); 
            }

            
            fclose($this->socket);

            
            return [null, true];
        }

        return [$payload, $final];
    }

    /**
     * Tell the socket to close.
     *
     * @param integer $status  http://tools.ietf.org/html/rfc6455#section-7.4
     * @param string  $message A closing message, max 125 bytes.
     */
    public function close($status = 1000, $message = 'ttfn')
    {
        if (!$this->isConnected()) {
            return null;
        }
        $status_binstr = sprintf('%016b', $status);
        $status_str = '';
        foreach (str_split($status_binstr, 8) as $binstr) {
            $status_str .= chr(bindec($binstr));
        }
        $this->send($status_str . $message, 'close', true);

        $this->is_closing = true;
        $this->receive(); 
    }

    protected function write($data)
    {
        $written = fwrite($this->socket, $data);
        if ($written === false) {
            $length = strlen($data);
            $this->throwException("Failed to write $length bytes.");
        }

        if ($written < strlen($data)) {
            $length = strlen($data);
            $this->throwException("Could only write $written out of $length bytes.");
        }
    }

    protected function read($length)
    {
        $data = '';
        while (strlen($data) < $length) {
            $buffer = fread($this->socket, $length - strlen($data));
            if ($buffer === false) {
                $read = strlen($data);
                $this->throwException("Broken frame, read $read of stated $length bytes.");
            }
            if ($buffer === '') {
                $this->throwException("Empty read; connection dead?");
            }
            $data .= $buffer;
        }
        return $data;
    }

    protected function throwException($message, $code = 0)
    {
        $meta = stream_get_meta_data($this->socket);
        if (!empty($meta['timed_out'])) {
            $code = ConnectionException::TIMED_OUT;
        }
        if (!empty($meta['eof'])) {
            $code = ConnectionException::EOF;
        }
        $json_meta = json_encode($meta);
        throw new ConnectionException("$message  Stream state: $json_meta", $code);
    }

    /**
     * Helper to convert a binary to a string of '0' and '1'.
     */
    protected static function sprintB($string)
    {
        $return = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $return .= sprintf("%08b", ord($string[$i]));
        }
        return $return;
    }
}

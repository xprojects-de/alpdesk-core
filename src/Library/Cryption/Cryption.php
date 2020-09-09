<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Cryption;

use Contao\System;

class Cryption {

  private string $key;

  public function __construct($useContaoKey = false) {
    if (!\in_array('sodium', get_loaded_extensions())) {
      throw new \Exception('The PHP sodium extension is not installed');
    }
    if ($useContaoKey) {
      $key = System::getContainer()->getParameter('contao.encryption_key');
      $key = substr($key, 0, 32);
      if (mb_strlen($key, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new \Exception('Key is not the correct size (must be 64 bytes).');
      }
      $this->key = $key;
    }
  }

  public function setKey(string $key): void {
    if (mb_strlen($key, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
      throw new \Exception('Key is not the correct size (must be 64 bytes).');
    }
    $this->key = $key;
  }

  public function safeEncrypt(string $message, $zeroKey = true): string {
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = base64_encode(
            $nonce .
            sodium_crypto_secretbox(
                    $message,
                    $nonce,
                    $this->key
            )
    );
    sodium_memzero($message);
    if ($zeroKey) {
      sodium_memzero($this->key);
    }
    return $cipher;
  }

  public function safeDecrypt(string $encrypted, $zeroKey = true): string {
    $decoded = base64_decode($encrypted);
    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
    $plain = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $this->key
    );
    if (!is_string($plain)) {
      throw new \Exception('Invalid Decryption');
    }
    sodium_memzero($ciphertext);
    if ($zeroKey) {
      sodium_memzero($this->key);
    }
    return $plain;
  }

}

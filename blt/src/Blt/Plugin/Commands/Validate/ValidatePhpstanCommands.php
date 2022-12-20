<?php

namespace Evolvingweb\Blt\Plugin\Commands\Validate;

use Acquia\Blt\Robo\BltTasks;

/**
 * Defines commands in the "custom" namespace.
 */
class ValidatePhpstanCommands extends BltTasks {

  /**
   * Run phpstan.
   *
   * @command validate:phpstan
   */
  public function validatePhpstan() {
    $this->say("Running phpstan...");
    $result = $this->taskExecStack()
      ->dir($this->getConfigValue('repo.root'))
      ->exec('./vendor/bin/phpstan analyse -c .phpstan.neon -l 0 --memory-limit=-1')
      ->run();
    if (!$result->wasSuccessful()) {
      $this->say($result->getMessage());
      $this->logger->error("phpstan failed, check the above logs.");
      throw new \Exception('phpstan failed!');
    }
  }

}

Feature: LoggerInterface
  In order to use LoggerInterface
  As a Psalm user
  I need Psalm to typecheck methods

  Background:
    Given I have the default psalm configuration
    And I have the following code preamble
      """
      <?php
      use Psr\Log\LoggerInterface;

      /**
       * @psalm-suppress InvalidReturnType
       * @return LoggerInterface
       */
      function getLogger() {}
      """

  Scenario: Required keys should be required
    Given I have the following code
      """
      getLogger()->emergency('foo', []);
      getLogger()->alert('foo', []);
      getLogger()->critical('foo', []);
      getLogger()->error('foo', []);
      getLogger()->warning('foo', []);
      getLogger()->notice('foo', []);
      getLogger()->info('foo', []);
      getLogger()->debug('foo', []);
      """
    When I run psalm
    Then I see these errors
      | Type            | Message |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
      | InvalidArgument | Missing placeholders in context: requiredKey1 |
    And I see no other errors

  Scenario: Ignored keys should be ignored
    Given I have the following code
      """
      getLogger()->emergency('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->alert('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->critical('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->error('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->warning('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->notice('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->info('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      getLogger()->debug('foo {ignoredKey1}', ['requiredKey1' => 'req']);
      """
    When I run psalm
    And I see no errors

  Scenario: A message string without templates in context should throw errors for missing keys
    Given I have the following code
      """
      getLogger()->emergency('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->alert('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->critical('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->error('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->warning('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->notice('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->info('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->debug('foo {bar} {baz}', ['bar' => 'bar', 'requiredKey1' => 'req']);
      """
    When I run psalm
    Then I see these errors
      | Type            | Message |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
    And I see no other errors

  Scenario: A message object without templates in context should throw errors for missing keys
    Given I have the following code
      """
      class Message
      {
          /**
           * @psalm-return 'foo {bar} {baz}'
           */
          public function __toString(): string
          {
              return 'foo {bar} {baz}';
          }
      }

      getLogger()->emergency(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->alert(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->critical(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->error(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->warning(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->notice(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->info(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      getLogger()->debug(new Message(), ['bar' => 'bar', 'requiredKey1' => 'req']);
      """
    When I run psalm
    Then I see these errors
      | Type            | Message |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
      | InvalidArgument | Missing placeholders in context: baz |
    And I see no other errors

  Scenario: An object with `__toString()` method should not throw errors
    Given I have the following code
      """
      class Message
      {
          public function __toString(): string
          {
              return 'foo';
          }
      }

      getLogger()->emergency(new Message(), ['requiredKey1' => 'req']);
      getLogger()->alert(new Message(), ['requiredKey1' => 'req']);
      getLogger()->critical(new Message(), ['requiredKey1' => 'req']);
      getLogger()->error(new Message(), ['requiredKey1' => 'req']);
      getLogger()->warning(new Message(), ['requiredKey1' => 'req']);
      getLogger()->notice(new Message(), ['requiredKey1' => 'req']);
      getLogger()->info(new Message(), ['requiredKey1' => 'req']);
      getLogger()->debug(new Message(), ['requiredKey1' => 'req']);
      """
    Then I see no errors

  Scenario: An object without `__toString()` method should throw errors
    Given I have the following code
      """
      class Message
      {
      }

      getLogger()->emergency(new Message(), ['requiredKey1' => 'req']);
      getLogger()->alert(new Message(), ['requiredKey1' => 'req']);
      getLogger()->critical(new Message(), ['requiredKey1' => 'req']);
      getLogger()->error(new Message(), ['requiredKey1' => 'req']);
      getLogger()->warning(new Message(), ['requiredKey1' => 'req']);
      getLogger()->notice(new Message(), ['requiredKey1' => 'req']);
      getLogger()->info(new Message(), ['requiredKey1' => 'req']);
      getLogger()->debug(new Message(), ['requiredKey1' => 'req']);
      """
    When I run psalm
    Then I see these errors
      | Type            | Message                                                                                                                      |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::emergency expects string\|Stringable, Message provided |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::alert expects string\|Stringable, Message provided     |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::critical expects string\|Stringable, Message provided  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::error expects string\|Stringable, Message provided     |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::warning expects string\|Stringable, Message provided   |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::notice expects string\|Stringable, Message provided    |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::info expects string\|Stringable, Message provided      |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::debug expects string\|Stringable, Message provided     |
    And I see no other errors

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

      $logger = getLogger();
      """

  Scenario: A message string without templates in context should throw errors for missing keys
    Given I have the following code
      """
      getLogger()->emergency('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->alert('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->critical('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->error('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->warning('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->notice('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->info('foo {bar} {baz}', ['bar' => 'bar']);
      getLogger()->debug('foo {bar} {baz}', ['bar' => 'bar']);
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

      getLogger()->emergency(new Message(), ['bar' => 'bar']);
      getLogger()->alert(new Message(), ['bar' => 'bar']);
      getLogger()->critical(new Message(), ['bar' => 'bar']);
      getLogger()->error(new Message(), ['bar' => 'bar']);
      getLogger()->warning(new Message(), ['bar' => 'bar']);
      getLogger()->notice(new Message(), ['bar' => 'bar']);
      getLogger()->info(new Message(), ['bar' => 'bar']);
      getLogger()->debug(new Message(), ['bar' => 'bar']);
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

      getLogger()->emergency(new Message(), []);
      getLogger()->alert(new Message(), []);
      getLogger()->critical(new Message(), []);
      getLogger()->error(new Message(), []);
      getLogger()->warning(new Message(), []);
      getLogger()->notice(new Message(), []);
      getLogger()->info(new Message(), []);
      getLogger()->debug(new Message(), []);
      """
    When I run psalm
    Then I see no errors

  Scenario: An object without `__toString()` method should throw errors
    Given I have the following code
      """
      class Message
      {
      }

      getLogger()->emergency(new Message(), []);
      getLogger()->alert(new Message(), []);
      getLogger()->critical(new Message(), []);
      getLogger()->error(new Message(), []);
      getLogger()->warning(new Message(), []);
      getLogger()->notice(new Message(), []);
      getLogger()->info(new Message(), []);
      getLogger()->debug(new Message(), []);
      """
    When I run psalm
    Then I see these errors
      | Type            | Message                                                                                                                      |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::emergency expects string, Message provided |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::alert expects string, Message provided     |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::critical expects string, Message provided  |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::error expects string, Message provided     |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::warning expects string, Message provided   |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::notice expects string, Message provided    |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::info expects string, Message provided      |
      | InvalidCast     | Message cannot be cast to string                                                  |
      | InvalidArgument | Argument 1 of Psr\Log\LoggerInterface::debug expects string, Message provided     |
    And I see no other errors

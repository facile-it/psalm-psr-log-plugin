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

  Scenario: An object with `__toString()` method should not throw errors
    Given I have the following code
      """
      class Message
      {
          /**
           * @psalm-suppress InvalidReturnType
           */
          public function __toString(): string
          {}
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

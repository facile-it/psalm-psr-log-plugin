<?php

declare(strict_types=1);

namespace Facile\Psalm\PsrLogPlugin\Tests;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /** @var array<string,string> */
    protected $config = [
        'psalm_path' => 'vendor/bin/psalm',
        'default_file' => 'somefile.php',
        'default_dir' => 'tests/_run/',
    ];

    /**
     * @Given I have the default psalm configuration
     */
    public function haveTheDefaultPsalmConfiguration(): void
    {
        $config = <<<'XML'
            <?xml version="1.0" ?>
            <psalm
              errorLevel="1"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xmlns="https://getpsalm.org/schema/config"
              xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
            >
                <projectFiles>
                      <directory name="." />
                </projectFiles>
                
                <issueHandlers>
                    <UnusedVariable errorLevel="suppress" />
                </issueHandlers>
              
                <plugins>
                    <pluginClass class="Facile\Psalm\PsrLogPlugin\Plugin"/>
                </plugins>
            </psalm>
            XML;

        $this->haveTheFollowingConfig($config);
    }
}

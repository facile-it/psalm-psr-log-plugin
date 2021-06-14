# psalm-psr-log-plugin

A Psalm plugin to check psr/log (PSR-3) usage.

## Features

- Suppress `ImplicitToStringCast` psalm errors when objects with a `__toString()` method are used as message
- Checks that all placeholders used in a message string are in the context array

## Usage

Include the plugin in your `psalm.xml` config file.

```xml
<psalm>
    <plugins>
        <pluginClass class="Facile\Psalm\PsrLogPlugin\Plugin"/>
    </plugins>
</psalm>
```

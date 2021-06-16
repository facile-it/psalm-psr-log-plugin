# psalm-psr-log-plugin

A Psalm plugin to check psr/log (PSR-3) usage.

## Features

- Suppress `ImplicitToStringCast` psalm errors when objects with a `__toString()` method are used as message
- Checks that all placeholders used in a message string are in the context array

## Example

This plugin checks for missing context keys for placeholders:

```php
/** @var \Psr\Log\LoggerInterface $logger */

$logger->info('This is a log message with {placeholder}', []);
  //                                                      ^ Error
  // InvalidArgument: Missing placeholders in context: placeholder
```

## Usage

Include the plugin in your `psalm.xml` config file.

```xml
<psalm>
    <plugins>
        <pluginClass class="Facile\Psalm\PsrLogPlugin\Plugin"/>
    </plugins>
</psalm>
```

### Required keys

if you want to always require keys in context, you can configure the plugin with the `requiredKey`:

```xml
<psalm>
    <plugins>
        <pluginClass class="Facile\Psalm\PsrLogPlugin\Plugin">
            <requiredKey>requestId</requiredKey>
            <requiredKey>environment</requiredKey>
        </pluginClass>
    </plugins>
</psalm>
```

### Ignored keys

if you want to ignore requirement for some key in context, you can configure the plugin with the `ignoredKey`.

This is useful when you have your logger that automatically injects it.

```xml
<psalm>
    <plugins>
        <pluginClass class="Facile\Psalm\PsrLogPlugin\Plugin">
            <ignoredKey>requestId</ignoredKey>
        </pluginClass>
    </plugins>
</psalm>
```

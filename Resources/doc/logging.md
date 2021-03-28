### Audit / Logging ###

This was a requirement to have a traceability of messages received/published.
In order to enable this you'll need to add "enable_logger" config to consumers or publishers.

```yaml
consumers:
    upload_picture:
        connection:       default
        exchange_options: {name: 'upload-picture', type: direct}
        queue_options:    {name: 'upload-picture'}
        callback:         upload_picture_service
        enable_logger: true
```

If you would like you can also treat logging from queues with different handlers in monolog, by referencing channel "phpamqplib"

# Full reference

The following JSON contains all configuration options that are supported
by default. However, additional configuration can be added and handled
by external asset handlers.

```json
{
    "frontend-assets": [
        {
            "source": {
                "type": "<type>",
                "url": "<provider url>",
                "version": "<version>",
                "revision-url": "<revision url>"
            },
            "target": {
                "type": "<type>",
                "path": "<path>",
                "revision-file": "<revision file>"
            },
            "vcs": {
                "type": "<type>"
            },
            "environments": {
                "merge": "<merge>",
                "map": "<map>"
            },
            "handler": "<type>"
        }
    ]
}
```

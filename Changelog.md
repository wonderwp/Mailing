# Changelog

## 1.1.0

- Minor breaking change : Refactored the mandrill mailer for a cleaner constructor implementation. The api key must now be passed to the constructor, whereas before, the constructor looked for it in the container.
- Mandrill Mailer `correctEncodingRecursive` method unit test
- Removed the website entry from the metadata as it was project specific. It can be passed via the `$opts` parameter if you used it before.

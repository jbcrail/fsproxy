# fsproxy

fsproxy is a RESTful filesystem proxy designed to centralize access to
numerous filesystems behind a single interface.

This was developed as a proof-of-concept and should not be used on a
publicly-accessible server.

## Quick Start

1. Install dependencies:

        $ curl -sS https://getcomposer.org/installer | php
        $ php composer.phar update

2. Start web server:

        $ bin/serve

3. Go to `http://localhost:8080/`

4. Enjoy!

## TODO

* Add flag to disable HTML interface.
* Add API token authentication.
* Cache directory listings to minimize roundtrips.
* Add connection pools.
* Sort directory listing via Javascript.
* Add stream support for large files.
* Normalize directory listing across all adapters (filter/sort).

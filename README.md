# Mailer

Testing project, personal sandbox, and home for a future PHP webmail. The only
supported protocol will be IMAP.

## Architecture

### Short term goal

Goal for this application is to decouple fully the server from the client.
Not speaking about the mail server here: decouple the PHP server from the
HTML client.

The server will only be a simple REST server which will provide primitives
for the webmail logic; Client will be a simple HTML static page with the UI
fully embeded in JavaScript code.

JavaScript framework choice has not been made yet, and the initial client will
probably be pure JavaScript with no framework, at the exception of AJAX request
handling.

### Long term goal

Once the server will be fully up and running, a layer for local data indexation
and caching will be done in order to leave the IMAP server alone.

IMAP servers are not the best in term of searching, so another goal will be to
plug an indexation server such as SolR.

### Future integration

Initial integration will be:

    * Caching: Redis

    * Local storage (mail copy and settings): SQL or Redis

    * Index: SolR

## Credits and licencing

This code is licenced under the GPLv3. Please see the LICENCE file.

Files under the lib/roundcube folder come from the Roundcube mail client. This
code is licenced under the GPLv3 - All credits goes to their original authors.
For more information about roundcube see http://roundcube.net/

## REST API specification

REST API will work as we can expect it to work, using the four widely used
HTTP verbs; I.e. GET, POST, PUT and DELETE.

Return status code will drive the return status, content will only be the
data.

Note that all list operations (which are GET commands) will accept those
parameters:

    * offset : start offset (default will always be 0)

    * length : limit (default will always be set and will be different for
      each use case, and may vary upon user configuration)

Top level folders such as INBOX, TRASH, SENT etc... will be managed as any
other folder. The only exception is that the DELETE operation may not work
upon those.

#### folder

    * GET folder : List root folders

    * GET folder/X : List subfolders

    * GET folder/X/list : List threads for this folder

    * DELETE folder/X : Delete folder, emails will be moved into INBOX if no
      other parameters are specified

#### mail

Mails are all about this software there is therefore no need to explain
what they do and why we expose an API for them.

    * GET mail/X : Get mail X

    * POST mail : Send a mail

    * DELETE mail/X : Delete mail X

PUT operation will not supported.

#### thread

Thread identifiers will be given when listing a folder content. Each thread
may contain one or more mail. Thread identifiers are decoupled from mail
identifiers.

    * GET thread/X : Get the full thread X

    * DELETE : thread/X : Delete the full thread X

PUT and POST are not supported; Threads will be managed by the server.

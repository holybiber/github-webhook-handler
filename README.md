# PHP handler for GitHub webhooks

If you want to deploy from GitHub to your own web server, this script might be
exactly what you need.

1. Put ``github.php`` somewhere on your PHP-enabled web server, and make it
   accessible for the outside world. For a bit more security add some random to the URL, e.g.
   http://example.com/webhook-handler-2r0fZiEddBNx24kuSztq/github.php

2. Somewhere on your server you need to have an update script that pulls your site
   from GitHub. The simplest would be ``cd myrepo; git pull``.
   You find a more robust version of this in ``deploy_scripts/pull_from_github.sh``

3. Look at ``.ht.config.example.json`` and prepare a ``.ht.config.json`` with your configuration.
   Make sure it's not accessible from the outside! In most apache configurations no files starting
   with ``.ht`` will be served at all so you can leave it here. Or move it to a secure location
   on your server (outside of web root). If you move it, make sure the PHP script knows where to find it.

   If you want email notification (yes, you want!), enter your email
   address to **email.to**. The emails will also be sent to the email of the Github
   user who pushed to the repository. Set **email.from** to something meaningful
   like github-push-notification@example.com.

   You can use it for several repositories or branches at the
   same time by adding more entries to the **endpoints** list. For each endpoint
   you need to set **endpoint.repo** to *"username/reponame"* and **endpoint.branch**
   to your branch. Write a secret random string to **endpoint.secret**.
   You can configure endpoints for different branches, for instance if you
   use different branches for development/production etc.

   Set **endpoint.run** to the path of your update script like ``/path/to/update/script.sh``.

   For clarity, describe what happened under **endpoint.description**.
   It will be used as subject in notification emails. This is especially
   helpful if you have multiple endpoints.

   The email will contain all the messages of the pushed commits and the output of your update script.

4. On the settings page of your GitHub repository, go to **Webhooks** and
   enter the public url of your ``github.php``. Set the content type to "application/json" and enter
   the same secret you stored in the config in **endpoint.secret**.

## Credits
It is based on https://gist.github.com/gka/4627519.
Here the script isn't checking anymore whether the sending IP address belongs to GitHub
but instead we're using a secret [as recommended](https://docs.github.com/en/developers/webhooks-and-events/webhooks/securing-your-webhooks). Besides, code quality was improved in many places.
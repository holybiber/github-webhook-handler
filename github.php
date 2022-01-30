<?php

/*
 * Endpoint for GitHub Webhook URLs
 * see: https://docs.github.com/en/developers/webhooks-and-events/webhooks
 *
 * All emails will be sent to the address specified in email->to in .ht.config.json
 * Errors will also be sent to that email address
 */
$configFilename = '.ht.config.json';

function run($config, $repoConfig, $payload) {
    // execute the specified script and record its output
    ob_start();
    passthru($repoConfig->run, $returnCode);
    $output = ob_get_contents();
    ob_end_flush();

    if (isset($config['email'])) {
        // send notification mail and CC the github user who pushed the commit
        $headers  = "From: {$config['email']['from']}\r\n";
        $headers .= "CC: {$payload->pusher->email}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        $body = '<p>The Github user <a href="https://github.com/'
            . $payload->pusher->name .'">@' . $payload->pusher->name . '</a>'
            . ' has pushed to <a href="' . $payload->repository->url . '">'
            . $payload->repository->url . '</a>:</p><ul>';

        foreach ($payload->commits as $commit) {
            $body .= '<li>' . $commit->message . '<br />';
            $body .= '<small>added: <b>' . count($commit->added)
                .'</b> &nbsp; modified: <b>' . count($commit->modified)
                .'</b> &nbsp; removed: <b>' . count($commit->removed)
                .'</b> &nbsp; <a href="' . $commit->url
                . '">read more</a></small></li>';
        }
        $body .= "</ul><p>GitHub webhook handler invoked action: {$repoConfig->description}.</p>";
        $body .= "<p>Output of the script:</p><pre>$output</pre>";
        $body .= "<p>Return code: $returnCode</p>";

        mail($config['email']['to'], $repoConfig->description . ($returnCode == 0)? " OK" : " ERROR", $body, $headers);
    }
}

try {
    if (!file_exists($configFilename))
        throw new Exception("Can't find $configFilename");
    $config = json_decode(file_get_contents($configFilename), true);

    $json = null;
    if ($_SERVER['CONTENT_TYPE'] == 'application/json')
        $json = file_get_contents('php://input');
    elseif ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded')
        $json = $_POST['payload'];
    $payload = json_decode($json);

    if (empty($payload)) {
        echo "GitHub webhook handler here. No payload detected. Doing nothing.";
    } else {
        $repoConfig = null;
        $branch = substr($payload->ref, 11);    // $payload->ref contains e.g. "refs/heads/main"
        foreach ($config['endpoints'] as $endpoint) {
            // check if we have a configuration for this repository and branch
            if ($payload->repository->url == 'https://github.com/' . $endpoint['repo']
                && $branch == $endpoint['branch']) {
                    $repoConfig = $endpoint;
                    break;
            }
        }
        if (empty($repoConfig))
            throw new Exception("No configuration found for {$payload->repository->url} on branch $branch");
        if (!empty($repoConfig->secret)) {
            $hash = 'sha1=' . hash_hmac('sha1', $payload, $repoConfig->secret);
            $headerHash = $_SERVER['HTTP_X_HUB_SIGNATURE'];
            if (!hash_equals($hash, $headerHash))
                throw new Exception("The recieved hash ($headerHash) doesn't match the computed one ($hash).");
        } // else, there's no secret configured, we assume it's ok
        run($config, $repoConfig, $payload);
    }

} catch (Exception $e) {
    echo $e->getMessage();
    if (!empty($config) && isset($config['to'])) {
        mail($config['to'], $e->getMessage(), (string) $e);
    }
}

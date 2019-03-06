# ReviewerCredits plugin

**NOTE: Please ensure you're using the correct branch. Use the [master branch](https://github.com/4Science/reviewercredits-ojs/tree/master) for OJS 3.x**

Plugin to enable the integration with ReviewerCredits (tested with OJS 3.1.1.4)

Copyright © 2015-2018 University of Pittsburgh
<br />Copyright © 2014-2018 Simon Fraser University Library
<br />Copyright © 2003-2018 John Willinsky

Licensed under GPL 2 or better.

Contributed by 4Science (http://www.4science.it).

## Features:

Integration with ReviewerCredits via REST API. This plugin implements the following features:
 * Create and approve a Peer Review Claim on the ReviewerCredits website;

## Install:

 * Copy the source into the PKP product's plugins/generic/reviewerCredits folder.
 * Run `tools/upgrade.php upgrade` to allow the system to recognize the new plugin.
 * Enable this plugin within the administration interface.

## Configuration:
 * The Journal manager must insert the ReviewerCredits Journal credentials into the plugin settings. To obtain them the Journal must be a user of ReviewerCredits website.
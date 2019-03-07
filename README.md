# Sampler

This module offers sampling of data for the Thunder performance project.

## Prerequisites
Your project should be setup to use composer for installing required modules. Projects, that are differnetly setup are
not supported.

This module provides drush and drupal console commands only. If you do not already use either drush or drupal console,
install one of them before continuing.

See https://docs.drush.org/en/master/install/ and https://drupalconsole.com/docs for more informations on how to install
them.

## Installation

In your project root do:

    composer require thunder/sampler
    
Then enable the sampler module, either with command line or in the admin ui.
Flush the caches and you are ready to go.

## Usage

Do not use the commands on aproduction system! it might slow your system down.

In your docroot call either:

    drush sampler:report
    
or

    drupal sampler:report

You should see a json containing the sampled data. If you prefer to put the information into a file add a filename as
parameter:

    drupal sampler:report --file=report.json
 
This will write the data into the file report.json in your docroot.

By default, the sampler replaces bundle names with generic names. if you want to see the actual bundle names in the
report, you can switch the behaviour with the anonymize option:

    drupal sampler:report --anonymize=0

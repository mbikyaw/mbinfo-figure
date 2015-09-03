# mbinfo-figure

Figure box for MBInfo Wordpress site

Figure images are stored in Google Cloud Storage bucket.

## Setup

Set Google Server key for MBInfo Figure plugin by using WP-CLI

    wp option mbinfo-figure-gapi-key 'xxxx'
    
    
## Testing

Setup your WP plugin test system by running

    bash bin/install-wp-test.sh
    
In the plugin folder, run `phpunit` unit test runner.    
    
    phpunit
    

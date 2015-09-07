# mbinfo-figure

Insert figure to MBInfo WordPress site.

Figure images are stored in Google Cloud Storage bucket.

## Setup

Set Google API Server key for MBInfo Figure plugin by using WP-CLI

    wp option mbinfo-figure-gapi-key 'xxxx'
    
## Management
    
Use command line runner to manage images and maintenance. For detail, check out:
    
    wp help mbi-figure
    
## Testing

Setup your WP plugin test system by running

    bash bin/install-wp-test.sh
    
Create a file with name `credentials.json` withing following
    
    {
      "mbinfoFigureGapiKey": "xxx"
    }
    
Change `xxx` with Google API Server key.     
    
In the plugin folder, run `phpunit` unit test runner.    
    
    phpunit
    

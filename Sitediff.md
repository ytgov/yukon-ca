# Sitediff

This document will walk you through the process of using Sitediff in your project.

[[_TOC_]]

## Setting up Sitediff

To use Sitediff you need to enable the addon. To do so, run the following command:

```
fin sitediff enable
```

Then, you need to init Sitediff settings by running a command like this (replace urls with before and after urls):

```
fin sitediff init "https://evolvingweb.ca" "https://ew:ewdev@staging.evolvingweb.ca"  --exclude='(.+\.pdf|.+\.jpg|.+\.doc|.+\.docx)'
```

The above command will crawl the pages and generate a config file in `./sitediff/sitediff.yaml`. At the bottom of this file, add the following snippet (replace domains in pattern and optionally the substitute property):

```
sanitization:
  - title: Ignore domain specific links
    pattern: '(staging\.evolvingweb\.ca|evolvingweb\.ca)'
    substitute: '__EVOLVING_WEB_CA__'
includes:
  - drupal.yaml
```

This will add default Drupal sanitizations so that Sitediff can run smoothly in Drupal without flagging tons of false positives. You can add more site specific sanitizations following the same pattern in this config file.

## Using Sitediff

Once Sitediff has been setup, to generate the diffs you need to execute:

```
fin sitediff diff
```

And finally, to view the diff, execute:

```
fin sitediff serve
```

This will launch a webserver in the Sitediff container. You can access its content at `http://sitediff.{PROJECT_DOMAIN}/`

## More information

You can find more information about Sitediff in the following links:

- https://sitediff.io/
- https://github.com/evolvingweb/sitediff
- https://evolvingweb.ca/blog/sitediff-compare-multiple-versions-website
ioMongoDBPlugin
===============

The ioMongoDBPlugin is a light wrapper that allows you to get the magic
of Doctrine's MongoDB ODM in your symfony1 project. In reality, using
the basics of Doctrine's ODM in symfony1 is VERY easy and so this plugin
doesn't do all that much.

Configuration
-------------

### Getting the ODM library

Before beginning, you'll need to get the ODM library. We recommend retrieving
it from github and placing it in the `lib/vendor/mongodb_odm` directory:

    git clone git://github.com/doctrine/mongodb-odm.git lib/vendor/mongodb_odm
    cd lib/vendor/mongodb_odm
    git submodule init
    git submodule update

By default, the plugin will look in `lib/vendor/mongodb_odm` for the library.
If you've placed it in another location, simply set a config value to point
to it. The best place to do this is in `ProjectConfiguration::setup()`:

    // config/ProjectConfiguration
    class ProjectConfiguration extends sfProjectConfiguration
    {
      public function setup()
      {
        // ...

        sfConfig::set('mongodb_odm_dir', '/path/to/odm');
      }
      // ...
    }

### Modifying the ODM Configuration

By default, the ODM will be configured with sensible defaults. If you'd
like to influence the configuration, you can do so by listening to the
`io_mongo_db.configure_odm` event. The subject of that event is the
`Doctrine\ODM\MongoDB\Configuration` object.

Usage
-----

The most important thing when using the ODM is do access the `DocumentManager`.
This can be accessed via the plugin configuration class anywhere via:

    sfApplicationConfiguration::getActive()
      ->getPluginConfiguration('ioMongoDBPlugin')
      ->getDocumentManager();

It can also be accessed from within any actions class:

    class myActions extends sfActions
    {
      public function executeUpdate(sfWebRequest $request)
      {
        // ...
        $user = new User();
        $user->setUsername('weaverryan');
        $user->setPassword('changeme');

        $dm = $this->getDocumentManager();
        $dm->persist($user);
        $dm->flush();
      }
    }
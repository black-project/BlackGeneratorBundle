BlackGeneratorBundle
=====================

The ``BlackGeneratorBundle`` extends the SensioGeneratorBundle and the default Symfony2 command line
interface by providing new interactive and intuitive commands for generating
code skeletons like bundles.

Installation
------------

`Download`_ the bundle and put it under the ``Black\Bundle`` namespace.
Then, like for any other bundle, include it in your Kernel class::

    public function registerBundles()
    {
        $bundles = array(
            ...

            new Black\Bundle\GeneratorBundle\BlackGeneratorBundle(),
        );

        ...
    }

List of Available Commands
--------------------------

The ``BlackGeneratorBundle`` comes with a new command that can be run in
interactive mode or not. The interactive mode asks you some questions to
configure the command parameters to generate the definitive code. The list of
new commands are listed below:

.. toctree::
   :maxdepth: 1

   commands/generate_bundle

.. _Download: http://github.com/sensio/BlackGeneratorBundle

Overriding Skeleton Templates
-----------------------------

.. versionadded:: 2.3
  The possibility to override the skeleton templates was added in 2.3.

All generators use a template skeleton to generate files. By default, the
commands use templates provided by the bundle under its ``Resources/skeleton``
directory.

You can define custom skeleton templates by creating the same directory and
file structure in ``APP_PATH/Resources/BlackGeneratorBundle/skeleton`` or
``BUNDLE_PATH/Resources/BlackGeneratorBundle/skeleton`` if you want to extend
the generator bundle (to be able to share your templates for instance in
several projects).

For instance, if you want to override the ``edit`` template for the CRUD
generator, create a ``crud/views/edit.html.twig.twig`` file under
``APP_PATH/Resources/BlackGeneratorBundle/skeleton``.

When overriding a template, have a look at the default templates to learn more
about the available templates, their path, and the variables they have access.

Instead of copy/pasting the original template to create your own, you can also
extend it and only override the relevant parts:

.. code-block:: jinja

  {# in app/Resources/BlackGeneratorBundle/skeleton/crud/actions/create.php.twig #}

  {# notice the "skeleton" prefix here -- more about it below #}
  {% extends "skeleton/crud/actions/create.php.twig" %}

  {% block phpdoc_header %}
       {{ parent() }}
       *
       * This is going to be inserted after the phpdoc title
       * but before the annotations.
  {% endblock phpdoc_header %}

Complex templates in the default skeleton are split into Twig blocks to allow
easy inheritance and to avoid copy/pasting large chunks of code.

In some cases, templates in the skeleton include other ones, like
in the ``crud/views/edit.html.twig.twig`` template for instance:

.. code-block:: jinja

  {% include 'crud/views/others/record_actions.html.twig.twig' %}

If you have defined a custom template for this template, it is going to be
used instead of the default one. But you can explicitly include the original
skeleton template by prefixing its path with ``skeleton/`` like we did above:

.. code-block:: jinja

  {% include 'skeleton/crud/views/others/record_actions.html.twig.twig' %}

You can learn more about this neat "trick" in the official `Twig documentation
<http://twig.sensiolabs.org/doc/recipes.html#overriding-a-template-that-also-extends-itself>`_.

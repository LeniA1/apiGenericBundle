# Generate your first controller

## Requirement

You need to have allready generated a bundle, that has an entity and it's form.

## Generate

Using this command, you ll generate a controller. Its route ll be created in your bundle resources/config/routing/**.yml. it ll be imported from resources/config/routing.yml

> lenim:generate:controller

Your new controller extends LeniM\ApiGenericBundle\Controller\GenericApiTrait\CrudAbstract and uses its parent methods to render data in a specific template.
It ll need a few information that you store in constant :
- repository
- entity
- formType

## Form requirement

Check if the formType defined in the controller constants is the one you want to use with this api.

## Testing

Open the generated test file in Tests/Controller/**Test.php and replace the value for the webPath
you can add here additionnal testing  that your system needs.

once you are done just run phpunit to make sure everything is fine

> phpunit
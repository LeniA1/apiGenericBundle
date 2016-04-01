# apiGenericBundle

Sick and tired of writing code for crud using restfull api ?  This is a very light and simple tool to avoid you writing code to build the simplest qnd most used part of an API.

Features include:

- An abstract controller that handle CRUD
- An abstract class to be able to test your elements


# Configuration

## FOSRestBundle

full documentation here : https://github.com/FriendsOfSymfony/FOSRestBundle

## JMSSerializerBundle

full documentation here : https://github.com/schmittjoh/JMSSerializerBundle

# Build your first controller

Just create a new controller class that extends LeniM\ApiGenericBundle\Controller\GenericApiTrait\CrudAbstract and uses its parent methods.
It ll need a few information that you store in constant :
- repository ()
- entity ()

It allows the method :
- crudGet(int, int)
- crudList(Request)
- crudCreate(Request)
- crudDelete(Request, int)
- crudUpdate(Request, int)

# Build your first unit test 

Just create a unit test class that extends LeniM\ApiGenericBundle\Tests\AbstractTest and uses its parent methods.
As this is a webtestcase, it ll need the web root path of this entity in your api in a method getWebPath()
All the 

# Going futher with nelmio/NelmioApiDocBundle

There are 2 point for creating a controller file for each entity even if it will be handled the sqme way :
- keep a full control of the routes
- generate a beautifull documentation with nelmio api doc bundle 

https://github.com/nelmio/NelmioApiDocBundle

# License

This bundle is under the MIT license. See the complete license in the bundle:

# Idable queries: relational package

This package provides the functions to execute SQL queries on relational databases.

## Overview

Idable queries are a set of packages that are at the core a multi-database wrapper.
While you can use it as a wrapper with the same functions and types per database, the libraries provide an abstraction for the database names and parameters.
At the base of the abstraction is the `Identifier` interface. It is nothing more than a library specific name for enums.

````
enum Users implements Indentifier
{
   case Users; // It is recommended use the enun name for the table/collection/set/... to make the identifier more universal
   case Name;
}
````
A backed enum is recommended to separate database name from the enum name.

A SQL query, `SELECT name FROM users WHERE name = 'me';`, can now be written as `SELECT ~Users:Name FROM ~Users:Users WHERE ~Users:Name = :Users:Name;`.
A Redis query like `HMSET users name "Hello" email "World"` can be written as `HMSET ~Users:Users ~Users:Name :Users:Name ~Users:Email :Users:Email`.
And so on with other database types.

> **note:** The placeholders are case-insensitive, but for the best compilation path in PHP it is recommended to use capitals.> **note:** The placeholders are case-insensitive, but for the best compilation path in PHP it is recommended to use capitals.

To make it easier to add multiple parameters an `CustomParameterIdentifier` attribute can be added to an `Identifier` instance.
This signals to the parameter functions that the parameter value will use a transformer to replace a single placeholder.

````
#[CustomParameterIdentfier('Xwero\IdableQueriesRedis\setParameterTransformer')]
enum Set implements Identfier
{
   case Users;
}
````
Now the Redis query can be written as `HMSET ~Users:Users :Set:Users`.
And depending on the values in the `IdableParameterCollection` instance the query will be changed.
Each database package will have predefined transformer functions. Creating the `Identifier` instance is up to the implementers.

The libraries will have map functions that transform the result of the query in an array-like structure where the key is an `Identifier` instance.
As an example the `SELECT ~Users:Name FROM ~Users:Users WHERE ~Users:Name = :Users:Name;` query result called with the `createMapFromFirstLevelResults` function will have the `$map[Users:Name]` value me.
This prevents typos when using the results.

The libraries are build with utility in mind. That is why the main functionality is in the functions, rather than in objects.
Use as much or as little as you like.

## Functions

### buildStatement

Transforms a query into a `Statement`. This will be the start of the chain in most cases.

For the parameters it is possible to use a `IdableParameterCollection` or an array depending on the placeholders in the query.

### executeStatement

Executes and optionally returns data for the prepared `Statement`. 
An output config can be added to make it consistent. Read more about the output behavior in the [Statement](#statement) section.

### executeTransaction

Allows to execute multiple `Statement` instances. On `Error` a rollback will be executed.

### getFirstLevelMap

This is a convenience function that combines `getRow` and `createMapFromFirstLevelResults`.

### getSecondLevelMapCollection

This is a convenience function that combines `getRows` and `createMapFromSecondLevelResults`.

### inArrayParameterTransformer

This function is used as the argument of the `CustomParameterIdentifier` attribute when an `IN` parameter needs to be replaced.

### multiInsertParameterTransformer

This function is used as the argument of the `CustomParameterIdentifier` attribute when multi insert values need to be replaced.

### PDOfyParameterPlaceholder

Makes sure a SQL database is not going to complain about characters it doesn't accept.

Is used by the `buildStatement` function.

## Types

### Connection

Implementation of the `RelationalConnection` interface

The class configures a few things on the `PDO` instance:

- The error mode is set to throw exceptions.
- The default fetch mode is set to associate arrays.
- When the `PDO` methods that return a statement it is the library's `Statement` class and not the default `PDOStatement` class.

This means `$staement = $connection->client->prepare('SELECT * FROM users;')` can be used with the functions that use a `Statement` instance as input.

### QueryReturnConfigRelational

Manipulate the output of the `executeStatement` function and `Statement->run` method with different booleans.

The `trueOnSuccess` boolean is in this package less useful as the underlying database methods also return a boolean.

### RelationalConnection

Interface for the connection class.

### Statement <a id="statement"></a>

Class to create a consistent language. 
It exposes the `PDO` instance with the client property and the queryString property exposes the database query.

This class has the methods:

- bindParameter: this method wraps `bindParam` for consistency.
- run: this method executes and gets the data if needed. It is possible to change the data output a `config` argument is added that accepts a `QueryReturnConfigRelational` instance otherwise, it returns the least amount of data.

> **note:** The least amount of data is based on the row amount and the field amount. For example, a single row with a single field return the value of the field instead of an array.
> If you want a consistent use the config argument.

## Tips

- Use the namespaces argument of the functions to prevent unexpected results because of typos or changed namespaces.
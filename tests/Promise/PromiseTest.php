<?php
namespace GraphQL\Tests\Promise;

use GraphQL\GraphQL;
use GraphQL\Promise\PromiseWrapper;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PromiseTest extends \PHPUnit_Framework_TestCase
{
    public function testAsyncResolving()
    {
        $logType = new ObjectType([
            'name' => 'log',
            'fields' => [
                'pid' => ['type' => Type::nonNull(Type::int())],
                'output' => ['type' => Type::nonNull(Type::string())],
            ],
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'log' => [
                    'type' => $logType,
                    'resolve' => function () {
                        return PromiseWrapper::wrap(new CliPromise('`which composer` diagnose'));
                    },
                ],
            ],
        ]);

        $schema = new Schema(['query' => $queryType]);

        $data = GraphQL::execute($schema, '{ log { pid output } }');

        $this->assertArrayNotHasKey('errors', $data);
        $this->assertArrayHasKey('pid', $data['data']['log']);
        $this->assertArrayHasKey('output', $data['data']['log']);
    }
}

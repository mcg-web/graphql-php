<?php
namespace GraphQL\Tests\Executor;

use GraphQL\Error\Error;
use GraphQL\Executor\Executor;
use GraphQL\Error\FormattedError;
use GraphQL\Language\Parser;
use GraphQL\Language\SourceLocation;
use GraphQL\Promise\PromiseWrapper;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use React\Promise\Promise;

class NonNullTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Exception */
    public $syncError;

    /** @var \Exception */
    public $nonNullSyncError;

    /** @var  \Exception */
    public $promiseError;

    /** @var  \Exception */
    public $nonNullPromiseError;

    public $throwingData;
    public $nullingData;
    public $schema;

    public function setUp()
    {
        $this->syncError = new \Exception('sync');
        $this->nonNullSyncError = new \Exception('nonNullSync');
        $this->promiseError = new \Exception('promise');
        $this->nonNullPromiseError = new \Exception('nonNullPromise');

        $this->throwingData = [
            'sync' => function () {
                throw $this->syncError;
            },
            'nonNullSync' => function () {
                throw $this->nonNullSyncError;
            },
            'promise' => function () {
                return PromiseWrapper::wrap(new Promise(function () {
                    throw $this->promiseError;
                }));
            },
            'nonNullPromise' => function () {
                return PromiseWrapper::wrap(new Promise(function () {
                    throw $this->nonNullPromiseError;
                }));
            },
            'nest' => function () {
                return $this->throwingData;
            },
            'nonNullNest' => function () {
                return $this->throwingData;
            },
            'promiseNest' => function () {
                return PromiseWrapper::wrap(new Promise(function (callable $resolve) {
                    $resolve($this->throwingData);
                }));
            },
            'nonNullPromiseNest' => function () {
                return PromiseWrapper::wrap(new Promise(function (callable $resolve) {
                    $resolve($this->throwingData);
                }));
            },
        ];

        $this->nullingData = [
            'sync' => function () {
                return null;
            },
            'nonNullSync' => function () {
                return null;
            },
            'promise' => function () {
                return PromiseWrapper::wrap(new Promise(function (callable $resolve) {
                    return $resolve(null);
                }));
            },
            'nonNullPromise' => function () {
                return PromiseWrapper::wrap(new Promise(function (callable $resolve) {
                    return $resolve(null);
                }));
            },
            'nest' => function () {
                return $this->nullingData;
            },
            'nonNullNest' => function () {
                return $this->nullingData;
            },
            'promiseNest' => function () {
                return PromiseWrapper::wrap(new Promise(function (callable $resolve) {
                    $resolve($this->nullingData);
                }));
            },
            'nonNullPromiseNest' => function () {
                return PromiseWrapper::wrap(new Promise(function (callable $resolve) {
                    $resolve($this->nullingData);
                }));
            },
        ];

        $dataType = new ObjectType([
            'name' => 'DataType',
            'fields' => function() use (&$dataType) {
                return [
                    'sync' => ['type' => Type::string()],
                    'nonNullSync' => ['type' => Type::nonNull(Type::string())],
                    'promise' => Type::string(),
                    'nonNullPromise' => Type::nonNull(Type::string()),
                    'nest' => $dataType,
                    'nonNullNest' => Type::nonNull($dataType),
                    'promiseNest' => $dataType,
                    'nonNullPromiseNest' => Type::nonNull($dataType),
                ];
            }
        ]);

        $this->schema = new Schema(['query' => $dataType]);
    }

    // Execute: handles non-nullable types

    /**
     * @it nulls a nullable field that throws synchronously
     */
    public function testNullsANullableFieldThatThrowsSynchronously()
    {
        $doc = '
      query Q {
        sync
      }
        ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'sync' => null,
            ],
            'errors' => [
                FormattedError::create(
                    $this->syncError->getMessage(),
                    [new SourceLocation(3, 9)]
                )
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, $ast, $this->throwingData, null, [], 'Q')->toArray());
    }

    public function testNullsANullableFieldThatThrowsInAPromise()
    {
        $doc = '
      query Q {
        promise
      }
        ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'promise' => null,
            ],
            'errors' => [
                FormattedError::create(
                    $this->promiseError->getMessage(),
                    [new SourceLocation(3, 9)]
                )
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, $ast, $this->throwingData, null, [], 'Q')->toArray());
    }

    public function testNullsASynchronouslyReturnedObjectThatContainsANonNullableFieldThatThrowsSynchronously()
    {
        // nulls a synchronously returned object that contains a non-nullable field that throws synchronously
        $doc = '
      query Q {
        nest {
          nonNullSync,
        }
      }
    ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'nest' => null
            ],
            'errors' => [
                FormattedError::create($this->nonNullSyncError->getMessage(), [new SourceLocation(4, 11)])
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, $ast, $this->throwingData, null, [], 'Q')->toArray());
    }

    public function testNullsAsynchronouslyReturnedObjectThatContainsANonNullableFieldThatThrowsInAPromise()
    {
        $doc = '
      query Q {
        nest {
          nonNullPromise,
        }
      }
    ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'nest' => null
            ],
            'errors' => [
                FormattedError::create($this->nonNullPromiseError->getMessage(), [new SourceLocation(4, 11)])
            ]
        ];
        $t = Executor::execute($this->schema, $ast, $this->throwingData, null, [], 'Q')->toArray();
        $this->assertArraySubset($expected, Executor::execute($this->schema, $ast, $this->throwingData, null, [], 'Q')->toArray());
    }

    public function testNullsAComplexTreeOfNullableFieldsThatThrow()
    {
        $doc = '
      query Q {
        nest {
          sync
          nest {
            sync
          }
        }
      }
        ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'nest' => [
                    'sync' => null,
                    'nest' => [
                        'sync' => null,
                    ]
                ]
            ],
            'errors' => [
                FormattedError::create($this->syncError->getMessage(), [new SourceLocation(4, 11)]),
                FormattedError::create($this->syncError->getMessage(), [new SourceLocation(6, 13)]),
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, $ast, $this->throwingData, null, [], 'Q')->toArray());
    }

    public function testNullsANullableFieldThatSynchronouslyReturnsNull()
    {
        $doc = '
      query Q {
        sync
      }
        ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'sync' => null,
            ]
        ];
        $this->assertEquals($expected, Executor::execute($this->schema, $ast, $this->nullingData, null, [], 'Q')->toArray());
    }

    public function test4()
    {
        // nulls a synchronously returned object that contains a non-nullable field that returns null synchronously
        $doc = '
      query Q {
        nest {
          nonNullSync,
        }
      }
        ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'nest' => null
            ],
            'errors' => [
                FormattedError::create('Cannot return null for non-nullable field DataType.nonNullSync.', [new SourceLocation(4, 11)])
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, $ast, $this->nullingData, null, [], 'Q')->toArray());
    }

    public function test5()
    {
        // nulls a complex tree of nullable fields that return null

        $doc = '
      query Q {
        nest {
          sync
          nest {
            sync
            nest {
              sync
            }
          }
        }
      }
    ';

        $ast = Parser::parse($doc);

        $expected = [
            'data' => [
                'nest' => [
                    'sync' => null,
                    'nest' => [
                        'sync' => null,
                        'nest' => [
                            'sync' => null
                        ]
                    ],
                ],
            ]
        ];
        $this->assertEquals($expected, Executor::execute($this->schema, $ast, $this->nullingData, null, [], 'Q')->toArray());
    }

    public function testNullsTheTopLevelIfSyncNonNullableFieldThrows()
    {
        $doc = '
      query Q { nonNullSync }
        ';

        $expected = [
            'errors' => [
                FormattedError::create($this->nonNullSyncError->getMessage(), [new SourceLocation(2, 17)])
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, Parser::parse($doc), $this->throwingData)->toArray());
    }

    public function testNullsTheTopLevelIfSyncNonNullableFieldReturnsNull()
    {
        // nulls the top level if sync non-nullable field returns null
        $doc = '
      query Q { nonNullSync }
        ';

        $expected = [
            'errors' => [
                FormattedError::create('Cannot return null for non-nullable field DataType.nonNullSync.', [new SourceLocation(2, 17)]),
            ]
        ];
        $this->assertArraySubset($expected, Executor::execute($this->schema, Parser::parse($doc), $this->nullingData)->toArray());
    }
}

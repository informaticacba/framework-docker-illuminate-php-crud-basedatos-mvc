<?php

use Mockery as m;

class DatabaseConnectionResolverTest extends \PHPUnit\Framework\TestCase {

	protected function tearDown(): void
	{
		m::close();
	}


	public function testConnectionsCanBeAddedAtConstruction()
	{
        $configs = array(
            'test1' => array(
                'foo' => 'bar'
            ),
        );

        $factory = m::mock('Database\Connectors\ConnectionFactory');

        $connectionMock = m::mock('stdClass');

        $factory->shouldReceive('make')->once()->with($configs['test1'])->andReturn($connectionMock);

		$resolver = new \Database\ConnectionResolver($configs, $factory);

		$this->assertTrue($resolver->hasConnection('test1'));

        $connection = $resolver->connection('test1');

        $this->assertSame($connectionMock, $connection);
	}

    public function testItReturnsADefaultConnection()
    {
        $configs = array(
            'test' => array(
                'foo' => 'bar'
            ),
        );

        $factory = m::mock('Database\Connectors\ConnectionFactory');

        $connectionMock = m::mock('stdClass');

        $factory->shouldReceive('make')->once()->with($configs['test'])->andReturn($connectionMock);

        $resolver = new \Database\ConnectionResolver($configs, $factory);

        $resolver->setDefaultConnection('test');

        $connection = $resolver->connection();

        $this->assertSame($connectionMock, $connection);
    }
}

<?php

use Mockery as m;

class DatabaseConnectorTest extends \PHPUnit\Framework\TestCase
{
    public function tearDown()
    {
        m::close();
    }


    public function testOptionResolution()
    {
        $connector = new Database\Connectors\Connector;
        $connector->setDefaultOptions(array(0 => 'foo', 1 => 'bar'));
        $this->assertEquals(array(0 => 'baz', 1 => 'bar', 2 => 'boom'), $connector->getOptions(array('options' => array(0 => 'baz', 2 => 'boom'))));
    }


    /**
     * @dataProvider mySqlConnectProvider
     */
    public function testMySqlConnectCallsCreateConnectionWithProperArguments($dsn, $config)
    {
        $connector = $this->getMockBuilder('Database\Connectors\MySqlConnector')->setMethods(array('createConnection', 'getOptions'))->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\' collate \'utf8_unicode_ci\'')->andReturn($connection);
        $connection->shouldReceive('execute')->once();
        $connection->shouldReceive('exec')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }


    public function mySqlConnectProvider()
    {
        return array(
            array('mysql:host=foo;dbname=bar', array('host' => 'foo', 'database' => 'bar', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8')),
            array('mysql:host=foo;port=111;dbname=bar', array('host' => 'foo', 'database' => 'bar', 'port' => 111, 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8')),
            array('mysql:unix_socket=baz;dbname=bar', array('host' => 'foo', 'database' => 'bar', 'port' => 111, 'unix_socket' => 'baz', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8')),
        );
    }


    public function testPostgresConnectCallsCreateConnectionWithProperArguments()
    {
        $dsn = 'pgsql:host=foo;dbname=bar;port=111';
        $config = array('host' => 'foo', 'database' => 'bar', 'port' => 111, 'charset' => 'utf8');
        $connector = $this->getMockBuilder('Database\Connectors\PostgresConnector')->setMethods(array('createConnection', 'getOptions'))->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($connection);
        $connection->shouldReceive('execute')->once();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }


    public function testPostgresSearchPathIsSet()
    {
        $dsn = 'pgsql:host=foo;dbname=bar';
        $config = array('host' => 'foo', 'database' => 'bar', 'schema' => 'public', 'charset' => 'utf8');
        $connector = $this->getMockBuilder('Database\Connectors\PostgresConnector')->setMethods(array('createConnection', 'getOptions'))->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($connection);
        $connection->shouldReceive('prepare')->once()->with("set search_path to public")->andReturn($connection);
        $connection->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }


    public function testSQLiteMemoryDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite::memory:';
        $config = array('database' => ':memory:');
        $connector = $this->getMockBuilder('Database\Connectors\SQLiteConnector')->setMethods(array('createConnection', 'getOptions'))->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }


    public function testSQLiteFileDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite:'.__DIR__;
        $config = array('database' => __DIR__);
        $connector = $this->getMockBuilder('Database\Connectors\SQLiteConnector')->setMethods(array('createConnection', 'getOptions'))->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }


    public function testSqlServerConnectCallsCreateConnectionWithProperArguments()
    {
        $config = array('host' => 'foo', 'database' => 'bar', 'port' => 111);
        $dsn = $this->getDsn($config);
        $connector = $this->getMockBuilder('Database\Connectors\SqlServerConnector')->setMethods(array('createConnection', 'getOptions'))->getMock();

        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testItThrowsExceptionOnFailedConnection()
    {
        $this->expectException('Database\Exception\ConnectionException');
        $this->expectExceptionMessage("Connection to 'dsn' failed: invalid data source name");

        $connector = new \Database\Connectors\MySqlConnector();

        $connector->createConnection("dsn", array(), array());
    }

    protected function getDsn(array $config)
    {
        extract($config);

        $port = isset($config['port']) ? ','.$port : '';

        if (in_array('dblib', PDO::getAvailableDrivers())) {
            return "dblib:host={$host}{$port};dbname={$database}";
        } else {
            return "sqlsrv:Server={$host}{$port};Database={$database}";
        }
    }
}

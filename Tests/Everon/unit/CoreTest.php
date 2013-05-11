<?php
namespace Everon\Test;

class CoreTest extends \Everon\TestCase
{
   
    public function testConstructor()
    {
        $Core = new \Everon\Core();
        $Core->shutdown();
        $this->assertInstanceOf('\Everon\Interfaces\Core', $Core);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRun(\Everon\Interfaces\Controller $Controller, \Everon\Interfaces\Factory $Factory)
    {
        $Core = $Factory->buildCore();
        $result = $Core->run($Controller);
        $this->assertTrue($result);
    }

    /**
     * @dataProvider dataProvider
     * @expectedException \Everon\Exception\InvalidControllerMethod
     * @expectedExceptionMessage Controller: "MyController" has no action: "WrongActionName" defined
     */
    public function testRunShouldThrowExceptionWhenInvalidControllerMethod(\Everon\Interfaces\Controller $Controller, \Everon\Interfaces\Factory $Factory)
    {
        $RouteItemMock = $this->getMock('Everon\Interfaces\ConfigItemRouter');
        $RouteItemMock->expects($this->atLeastOnce())
            ->method('getAction')
            ->will($this->returnValue('WrongActionName'));

        $RouterMock = $this->getMock('Everon\Interfaces\Router');
        $RouterMock->expects($this->atLeastOnce())
            ->method('getCurrentRoute')
            ->will($this->returnValue($RouteItemMock));

        $ControllerMock = $this->getMock('Everon\Test\MyController', [], [], '', false);
        $ControllerMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('MyController'));

        $ControllerMock->expects($this->atLeastOnce())
            ->method('getRouter')
            ->will($this->returnValue($RouterMock));

        $ViewMock = $this->getMock('Everon\Interfaces\View');
        $ControllerMock->expects($this->any())
            ->method('getView')
            ->will($this->returnValue($ViewMock));

        $Core = $Factory->buildCore();
        $result = $Core->run($ControllerMock);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testBeforeRun(\Everon\Interfaces\Controller $Controller, \Everon\Interfaces\Factory $Factory)
    {
        $ViewMock = $this->getMockBuilder('Everon\Test\MyView')
            ->setMethods([
                'testOne',
                'beforeTestOne',
                'afterTestOne',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $ViewMock->expects($this->never())
            ->method('testOne')
            ->will($this->returnValue(true));

        $ViewMock->expects($this->never())
            ->method('beforeTestOne')
            ->will($this->returnValue(true));

        $ViewMock->expects($this->never())
            ->method('afterTestOne')
            ->will($this->returnValue(true));
        
        $RouteItemMock = $this->getMock('Everon\Interfaces\ConfigItemRouter');
        $RouteItemMock->expects($this->atLeastOnce())
            ->method('getAction')
            ->will($this->returnValue('testOne'));

        $RouterMock = $this->getMock('Everon\Interfaces\Router');
        $RouterMock->expects($this->atLeastOnce())
            ->method('getCurrentRoute')
            ->will($this->returnValue($RouteItemMock));

        $ControllerMock = $this->getMock('Everon\Test\MyController', [], [], '', false);
        $ControllerMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('MyController'));

        $ControllerMock->expects($this->atLeastOnce())
            ->method('getRouter')
            ->will($this->returnValue($RouterMock));

        $ControllerMock->expects($this->once())
            ->method('getView')
            ->will($this->returnValue($ViewMock));

        $ControllerMock->expects($this->once())
            ->method('beforeTestOne')
            ->will($this->returnValue(false));

        $ControllerMock->expects($this->never())
            ->method('testOne')
            ->will($this->returnValue(true));

        $Core = $Factory->buildCore();
        $result = $Core->run($ControllerMock);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAfterRun(\Everon\Interfaces\Controller $Controller, \Everon\Interfaces\Factory $Factory)
    {
        $ViewMock = $this->getMockBuilder('Everon\Test\MyView')
            ->setMethods([
            'testOne',
            'beforeTestOne',
            'afterTestOne',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $ViewMock->expects($this->once())
            ->method('testOne')
            ->will($this->returnValue(true));

        $ViewMock->expects($this->once())
            ->method('beforeTestOne')
            ->will($this->returnValue(true));

        $ViewMock->expects($this->once())
            ->method('afterTestOne')
            ->will($this->returnValue(false));

        $RouteItemMock = $this->getMock('Everon\Interfaces\ConfigItemRouter');
        $RouteItemMock->expects($this->atLeastOnce())
            ->method('getAction')
            ->will($this->returnValue('testOne'));

        $RouterMock = $this->getMock('Everon\Interfaces\Router');
        $RouterMock->expects($this->atLeastOnce())
            ->method('getCurrentRoute')
            ->will($this->returnValue($RouteItemMock));

        $ControllerMock = $this->getMockBuilder('Everon\Test\MyController')
            ->setMethods([
                'getRouter',
                'getView',
                'beforeTestOne',
                'testOne',  
                'afterTestOne'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $ControllerMock->expects($this->atLeastOnce())
            ->method('getRouter')
            ->will($this->returnValue($RouterMock));

        $ControllerMock->expects($this->atLeastOnce())
            ->method('getView')
            ->will($this->returnValue($ViewMock));

        $ControllerMock->expects($this->once())
            ->method('beforeTestOne')
            ->will($this->returnValue(true));

        $ControllerMock->expects($this->once())
            ->method('testOne')
            ->will($this->returnValue(true));
        
        $ControllerMock->expects($this->once())
            ->method('afterTestOne')
            ->will($this->returnValue(true));

        $Core = $Factory->buildCore();
        $result = $Core->run($ControllerMock);
        $this->assertFalse($result);
    }

    public function dataProvider()
    {
        /**
         * @var \Everon\Interfaces\DependencyContainer $Container
         * @var \Everon\Interfaces\Factory $Factory
         */        
        list($Container, $Factory) = $this->getContainerAndFactory();
        
        $View = $this->getMockBuilder('Everon\Interfaces\View')
            ->disableOriginalConstructor()
            ->getMock();

        $ModelManager = $this->getMockBuilder('Everon\Interfaces\ModelManager')
            ->disableOriginalConstructor()
            ->getMock();

        $Controller = $Factory->buildController('MyController', $View, $ModelManager, 'Everon\Test');

        return [
            [$Controller, $Factory]
        ];
    }

}


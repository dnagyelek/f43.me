<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\Flickr;
use Guzzle\Http\Exception\RequestException;

class FlickrTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://www.flickr.com/photos/palnick/15000967101/in/photostream/lightbox/', true),
            array('http://www.flickr.com/photos/palnick/15000967102/', true),
            array('https://farm6.staticflickr.com/5581/15000967103_8eb7552825_n.jpg', true),
            array('http://farm6.static.flickr.com/5581/15000967104_8eb7552825_n.jpg', true),
            array('http://farm6.static.flicker.com/5581/15000967104_8eb7552825_n.jpg', false),
            array('http://farm6.static.flickr.com/5581/1500096710_8eb7552825_n.jpg', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnValue('test'));

        $flickr = new Flickr($guzzle, 'apikey');
        $this->assertEquals($expected, $flickr->match($url));
    }

    public function testContent()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('stat' => 'ok', 'sizes' => array('size' => array(
                    array('label' => 'Medium', 'source' => 'https://0.0.0.0/medium.jpg'),
                    array('label' => 'Large', 'source' => 'https://0.0.0.0/large.jpg')
                )))),
                $this->returnValue(array()),
                $this->throwException(new RequestException())
            ));

        $flickr = new Flickr($guzzle, 'apikey');

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('http://www.flickr.com/photos/palnick/15000967102/');

        // consecutive calls
        $this->assertEquals('<img src="https://0.0.0.0/large.jpg" />', $flickr->getContent());
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());
    }
}

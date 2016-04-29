<?php
/**
 * Created by PhpStorm.
 * User: nicholasp
 * Date: 2016/04/29
 * Time: 1:19 PM
 */

namespace Synaq\ZasaBundle\Tests\Connector;


use Synaq\CurlBundle\Curl\Response;
use Synaq\CurlBundle\Curl\Wrapper;
use Synaq\ZasaBundle\Connector\ZimbraConnector;
use Mockery as m;

class StoreSessionKeyTest extends ZimbraConnectorTestCase
{
    /**
     * @var ZimbraConnector
     */
    private $connector;

    /**
     * @test
     */
    public function shouldNotAuthOnConstructionIfSessionFileIsPresent()
    {
        $this->constructConnectorWithSessionFile(__DIR__ . '/Fixtures/token');
        $this->client->shouldNotHaveReceived('post');
    }

    /**
     * @test
     */
    public function shouldStoreAuthTokenInSessionFile()
    {
        $sessionFilePath = '/tmp/test-token';

        $token = '0_a503cf41a251d0468edc9f2ce885c31c939668f7_69643d33363a65306661666438392d313336302d313164392d383636312d3030306139356439386566323b6578703d31333a313435373937393739383633343b61646d696e3d313a313b747970653d363a7a696d6272613b7469643d393a3330393336323831393b';
        $authResponse = "<AuthResponse xmlns=\"urn:zimbraAdmin\">
                                <authToken>$token</authToken>
                                <lifetime>43200000</lifetime>
                            </AuthResponse>";

        $this->client->shouldReceive('post')->andReturn(
            new Response($this->httpOkHeaders.$this->soapHeaders.$authResponse.$this->soapFooters)
        );

        $this->constructConnectorWithSessionFile($sessionFilePath);
        $this->connector->login();
        $this->assertEquals($token, file_get_contents($sessionFilePath));
    }

    /**
     * @test
     */
    public function shouldUseAuthTokenFromSessionFile()
    {
        $getAccountResponse = '<GetAllAccountsResponse xmlns="urn:zimbraAdmin">
                    <account name="test@test.com" id="bc85eaf1-dfe0-4879-b5e0-314979ae0009">
                        <a n="attribute-1">value-1</a>
                        <a n="attribute-2">value-2</a>
                    </account>
                </GetAllAccountsResponse>';
        $this->client->shouldReceive('post')->andReturn(
            new Response($this->httpOkHeaders.$this->soapHeaders.$getAccountResponse.$this->soapFooters)
        );

        $this->constructConnectorWithSessionFile(__DIR__ . '/Fixtures/token');
        $this->connector->getAccounts('test.com');

        $expected = "    <context xmlns=\"urn:zimbra\">\n" .
                    "      <authToken>dummy-auth-token</authToken>\n" .
                    "    </context>\n";
        $this->client->shouldHaveReceived('post')->with(m::any(), m::on(function($actual) use ($expected) {

            return strstr($actual, $expected) !== false;
        }), m::any(), m::any(), m::any())->once();
    }

    protected function constructConnectorWithSessionFile($sessionFile)
    {
        $server = 'https://my-server.com:7071/service/admin/soap';
        $username = 'admin@my-server.com';
        $password = 'my-password';

        $this->connector = new ZimbraConnector($this->client, $server, $username, $password, true, $sessionFile);
    }
}

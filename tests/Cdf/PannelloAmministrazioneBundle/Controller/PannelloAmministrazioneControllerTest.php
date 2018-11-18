<?php

use Cdf\BiCoreBundle\Tests\Utils\FifreeWebtestcaseAuthorizedClient;

class PannelloAmministrazioneControllerTest extends FifreeWebtestcaseAuthorizedClient
{
    /*
     * @test
     */
    public function testSecuredAdminpanelIndex()
    {
        $client = $this->client;
        $url = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_homepage');
        //$this->assertContains('DoctrineORMEntityManager', get_class($em));

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());

        /*$urlcc = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_clearcache');
        $client->request('GET', $urlcc, array("env" => "prod"));
        dump($client->getResponse());
        exit;
        $this->assertTrue($client->getResponse()->isSuccessful());*/

        $urlsc = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_symfonycommand');
        $client->request('GET', $urlsc, array("symfonycommand" => "list"));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $urluc = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_unixcommand');
        $client->request('GET', $urluc, array("unixcommand" => "ls -all"));
        $this->assertTrue($client->getResponse()->isSuccessful());

        //Restart client per caricare il nuovo bundle
        $client->reload();
        $urlge = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_generateentity');
        $client->request('GET', $urlge, array("file" => "wbadmintest.mwb"));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $client->reload();
        $urlas = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_aggiornaschemadatabase');
        $client->request('GET', $urlas);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $client->reload();
        $urlgf = $client->getContainer()->get('router')->generate('fi_pannello_amministrazione_generateformcrud');
        $client->request('GET', $urlgf, array("entityform" => "Prova"));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $apppath = new \Cdf\PannelloAmministrazioneBundle\DependencyInjection\ProjectPath($client->getContainer());
        $appbundlepath = $apppath->getSrcPath() . DIRECTORY_SEPARATOR;
        $checkentitybaseprova = $appbundlepath . "Entity" . DIRECTORY_SEPARATOR . "BaseProva.php";
        $checkentityprova = $appbundlepath . "Entity" . DIRECTORY_SEPARATOR . "Prova.php";
        $checktypeprova = $appbundlepath . "Form" . DIRECTORY_SEPARATOR . "ProvaType.php";
        $checkviewsprova = $apppath->getSrcPath() . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "Prova";
        $checkindexprova = $apppath->getSrcPath() . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "Prova" . DIRECTORY_SEPARATOR . "Crud" . DIRECTORY_SEPARATOR . "index.html.twig";

        $this->assertTrue(file_exists($checkentitybaseprova));
        $this->assertTrue(file_exists($checkentityprova));
        $this->assertTrue(file_exists($checktypeprova));
        $this->assertTrue(file_exists($checkviewsprova));
        $this->assertTrue(file_exists($checkindexprova));

        cleanFilesystem();
        //dump($client->getResponse());
    }
}
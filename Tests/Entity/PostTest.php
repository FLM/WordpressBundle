<?php

namespace Hypebeast\WordpressBundle\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Hypebeast\WordpressBundle\Entity\Comment;
use Hypebeast\WordpressBundle\Entity\Post;
use Hypebeast\WordpressBundle\Entity\User;

class PostTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    protected function setUp()
    {
        parent::setUp();

        $kernel = static::createKernel();
        $kernel->boot();

        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown()
    {
        $this->em->getConnection()->rollback();

        parent::tearDown();
    }

    /**
     * Create post test
     *
     * @dataProvider postProvider
     */
    public function testNewPost($title, $content, $userId)
    {
        $post = new Post();
        $post->setTitle($title);
        $post->setName($title);
        $post->setContent($content);
        $post->setExcerpt('setPostExcerpt');
        $post->setUser($this->getUserRepository()->find($userId));

        $this->em->persist($post);
        $this->em->flush();

        $result = $this->getPostRepository()
            ->createQueryBuilder('post')
            ->orderBy('post.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($title, $result->getTitle());
        $this->assertEquals($content, $result->getContent());
        $this->assertEquals($userId, $result->getUser()->getId());
        $this->assertInternalType('string', $result->getUser()->getMetas()->get(1)->getKey());

        return $post;
    }

    public function testGetComments()
    {
        $post = $this->getPostRepository()->findOneById(1);

        $this->assertCount(1, $post->getComments());
        $this->assertEquals('Mr WordPress', $post->getComments()->first()->getAuthor());
        $this->assertEmpty($post->getComments()->first()->getAuthorEmail());
        $this->assertEquals('http://wordpress.org/', $post->getComments()->first()->getAuthorUrl());
        $this->assertEmpty($post->getComments()->first()->getAuthorIp());
        $this->assertNotEmpty($post->getComments()->first()->getContent());
        $this->assertEquals($post, $post->getComments()->first()->getPost());
    }

    public function testNewComment()
    {
        $post = $this->getPostRepository()->findOneById(1);

        $comment = new Comment();
        $comment->setAuthor('Lorem');
        $comment->setAuthorEmail('lorem@example.com');
        $comment->setContent('The message lorem ipsum dolor sit amet.');
        $comment->setPost($post);

        $this->em->persist($comment);
        $this->em->flush();

        $this->assertCount(2, $post->getComments());
    }

    /**
     * Get page meta
     */
    public function testGetPostMetasByKey()
    {
        $page = $this->getPostRepository()->findOneByType('page');

        $this->assertEquals(
            $page->getMetasByKey($page->getMetas()->get(0)->getKey())->first()->getValue(),
            $page->getMetas()->get(0)->getValue()
        );
    }

    /**
     * Create new post with a non-exist user id
     *
     * @expectedException ErrorException
     */
    public function testNewPostWithNonExistUser()
    {
        $post = $this->testNewPost('Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.', 999);
    }

    /**
     * Return a post with user id equal to zero
     */
    public function testPostWithUserIdZero()
    {
        // No solution to this issue yet, test skip.
        $this->markTestSkipped();

        $post = $this->getPostRepository()->find(5);

        $post->getUser(); // return a user proxy

        $post->getUser()->getID(); // throw an EntityNotFoundException
    }

    public function postProvider()
    {
        return array(
            array('Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.', 1),
            array('Sed ut perspiciatis unde', 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.', 1)
        );
    }

    protected function getPostRepository()
    {
        return $this->em->getRepository('HypebeastWordpressBundle:Post');
    }

    protected function getUserRepository()
    {
        return $this->em->getRepository('HypebeastWordpressBundle:User');
    }
}
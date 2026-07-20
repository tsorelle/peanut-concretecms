<?php
namespace Peanut\content\db;


use Peanut\content\db\model\entity\ContentAuthor;
use Peanut\content\db\model\entity\ContentItem;
use Peanut\content\db\model\entity\ContentVersion;
use Peanut\content\db\model\repository\ContentAuthorsRepository;
use Peanut\content\db\model\repository\ContentRepository;
use Peanut\content\db\model\repository\ContentVersionsRepository;
use Tops\db\TQuery;
use Tops\sys\TUser;

class ContentManager
{
    private ContentRepository $contentRepository;

    public function getVersionContent($versionId)
    {
        return $this->getContentVersionsRepository()->get($versionId);
    }

    public function getAuthorByAccountId(int $accountId)
    {
        return $this->getContentAuthorsRepository()->getAuthorByAccountId($accountId);
    }

    public function saveContent(int $contentId, string $content, bool $final = false)
    {
        $version = new ContentVersion();
        $version->contentId = $contentId;
        $version->content = $content;
        $repository = $this->getContentVersionsRepository();
        if ($final) {
            $repository->removeVersions($contentId);
        }
        $id = $repository->insert($version);
        $result = new \stdClass();
        $result->id = $id;
        $result->dateCreated = (new \DateTime())->format('Y-m-d H:i:s');
        return $result;
    }

    public function removeVersions($contentId)
    {
        $this->getContentVersionsRepository()->removeVersions($contentId);
    }

    private function getContentRepository() : ContentRepository {
        if (!isset($this->contentRepository)) {
            $this->contentRepository = new ContentRepository();
        }
        return $this->contentRepository;
    }

    private ContentVersionsRepository $contentVersionsRepository;

    public function getContentByContentId($contentId)
    {
        return $this->getContentVersionsRepository()->getLatestVersion($contentId);
    }

    private function getContentVersionsRepository() : ContentVersionsRepository {
        if (!isset($this->contentVersionsRepository)) {
            $this->contentVersionsRepository = new ContentVersionsRepository();
        }
        return $this->contentVersionsRepository;
    }

    private ContentAuthorsRepository $contentAuthorsRepository;
    private function getContentAuthorsRepository() : ContentAuthorsRepository {
        if (!isset($this->contentAuthorsRepository)) {
            $this->contentAuthorsRepository = new ContentAuthorsRepository();
        }
        return $this->contentAuthorsRepository;
    }

    public function getTitle($title, $authorId, $context) {
        return $this->getContentRepository()->getTitle($title, $authorId, $context);
    }
    public function getTitleByContentId($contentId) {
        return $this->getContentRepository()->get($contentId);
    }

    public function getSharedTitle($title, $context) {
        return $this->getContentRepository()->getSharedTitle($title, $context);
    }

  /**
     * @param null $authorId
     * @param $title
     * @param $context
     * @param string $description
     * @param string $content
     * @param bool $shared
     * @return mixed|ContentItem
     */
    public function createTitle(mixed $authorId, string $title, string $context, $description, string $content, bool $shared=false): ContentItem
    {
        $contentRepo = new ContentRepository();
        $contentItem = $contentRepo->getTitle($title, $authorId, $context);
        if (!$contentItem) {
            $contentItem = new ContentItem();
            $contentItem->title = $title;
            $contentItem->description = $description;
            $contentItem->authorId = $authorId;
            $contentItem->context = $context;
            $contentItem->active = 1;
            $contentItem->shared = $shared;
            $contentId = $contentRepo->insert($contentItem);
            $contentItem->id = $contentId;

        }
        $versionsRepo = new ContentVersionsRepository();
        $version = new ContentVersion();
        $version->contentId = $contentItem->id;
        $version->content = $content;
        $versionsRepo->insert($version);

        return $contentItem;
    }

    public function updateTitle(ContentItem $contentItem)
    {
        $this->getContentRepository()->update($contentItem);
    }

    public function getContentByVersionId($versionId)
    {
        return $this->getContentVersionsRepository()->get($versionId);
    }

    public function getLatestVersionContent($contentId)
    {
        $version = $this->getContentVersionsRepository()->getLatestVersion($contentId);
        if ($version) {
            return $version->content;
        }
        return '';
    }

    public function removeContent($contentId, $permanent=false) : ?ContentItem
    {
        $query = new TQuery();
        $contentRepo = $this->getContentRepository();
        $versionsRepo = $this->getContentVersionsRepository();
        /** @var ContentItem $item */
        $item = $contentRepo->get($contentId);
        if (!$item) {
            return null;
        }

        $versionsRepo->removeVersions($contentId, $permanent);
        if ($permanent) {
            $contentRepo->delete($contentId);
        }
        else {
            $contentRepo->remove($contentId);
        }
        return $item;
    }

    public function getContentVersions($contentId)
    {
        return $this->getContentVersionsRepository()->getVersionsByContentId($contentId);
    }

    public function getVersionList($contentId)
    {
        return $this->getContentVersionsRepository()->getVersionList($contentId);
    }

    public function createAuthor($accountId, $fullName) {
        $author = $this->getAuthorByAccountId($accountId);
        if (!$author) {
            $author = new ContentAuthor();
            $author->accountId = $accountId;
            $author->fullName = $fullName;
            $author->id  = $this->getContentAuthorsRepository()->insert($author);
        }
        return $author;
    }


    public function getAuthorTitles(string $context,$authorId=null) : array {
        $repo = $this->getContentRepository();
        return $repo->getTitlesListByAuthor($authorId,$context);
    }

    public function getSharedTitlesList(string $context, $excludeAuthor=null) : array
    {
        $repo = $this->getContentRepository();
        return $repo->getSharedTitlesList($context,$excludeAuthor);
    }

    public function getLists($context, $authorId, $sharedOnly = false) : \stdClass
    {
        $response = new \stdClass();
        if ($sharedOnly) {
            $response->shared = $this->getSharedTitlesList($context);
        }
        else {
            $response->titles = $this->getAuthorTitles($context,$authorId);
            $response->shared = $this->getSharedTitlesList($context,$authorId);
        }
        return $response;
    }

    public function getAuthorId($request
    )
    {
        $authorId = $request->authorId ?? null;
        if (!$authorId) {
            $accountId = TUser::getCurrent()->getId();
            $author = $this->getAuthorByAccountId($accountId);
            if (!$author) {
                return false;
            }
            return $author->id;
        }
        return $authorId;
    }

}
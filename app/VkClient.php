<?php

namespace app;


use VK\Client\VKApiClient;

class VkClient
{
    public const ORDER_CHRONOLOGICAL = 0;
    public const ORDER_REVERSE_CHRONOLOGICAL = 1;
    /**
     * @var VKApiClient
     */
    private $apiClient;
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @param VKApiClient $apiClient
     * @param string $accessToken
     */
    public function __construct(VKApiClient $apiClient, string $accessToken)
    {
        $this->apiClient   = $apiClient;
        $this->accessToken = $accessToken;
    }

    /**
     * @param int $albumId
     * @return string
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getImageUploadServerUrl(int $albumId): string
    {
        $response = $this->apiClient
            ->photos()
            ->getUploadServer($this->accessToken, ['album_id' => $albumId]);

        return $response['upload_url'];
    }

    /**
     * @param int $albumId
     * @param string $fileName
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function uploadImage(int $albumId, string $fileName)
    {
        $uploader = new VkImageUploader($this->getImageUploadServerUrl($albumId));
        $data = $uploader->upload($fileName);
        $this->saveUploadedImage($data);
    }

    /**
     * @param int $userId
     * @param int $albumId
     * @param int $order
     * @return mixed
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getPhotos(int $userId, int $albumId, int $order = self::ORDER_CHRONOLOGICAL)
    {
        return $this->apiClient
            ->photos()
            ->get($this->accessToken, [
                'rev'      => $order,
                'owner_id' => $userId,
                'album_id' => $albumId,
            ]);
    }

    /**
     * @param int $userId
     * @param int $albumId
     * @param int $count
     * @param int $offset
     * @return mixed
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getPhotosPage(int $userId, int $albumId, int $count, int $offset = 0)
    {
        return $this->apiClient
            ->photos()
            ->get($this->accessToken, [
                'rev'      => self::ORDER_CHRONOLOGICAL,
                'owner_id' => $userId,
                'album_id' => $albumId,
                'offset'   => $offset,
                'count'    => $count
            ]);
    }

    /**
     * @param int $userId
     * @param int $albumId
     * @param int $count
     * @param int $offset
     * @return \Generator
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function iteratePhotos(int $userId, int $albumId, int $count, int $offset = 0)
    {
        while (true) {
            $photos = $this->getPhotosPage($userId, $albumId, $count, $offset);
            if (!$photos['items']) {
                break;
            }

            yield from $photos['items'];
            $offset += $count;
        }
    }

    /**
     * @param int $vkUserId
     * @return mixed
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getAlbums(int $vkUserId)
    {
        return $this->apiClient
            ->photos()
            ->getAlbums($this->accessToken, ['owner_id' => $vkUserId]);
    }

    /**
     * @param array $uploadData
     * @return mixed
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    private function saveUploadedImage(array $uploadData)
    {
        return $this->apiClient
            ->photos()
            ->save($this->accessToken, [
                'server'      => $uploadData['server'],
                'photos_list' => stripslashes($uploadData['photos_list']),
                'album_id'    => $uploadData['aid'],
                'hash'        => $uploadData['hash']
            ]);
    }

    /**
     * @param int $vkUserId
     * @return mixed
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getUser(int $vkUserId)
    {
        $userList = $this->apiClient
            ->users()
            ->get($this->accessToken, [
                'user_ids' => [$vkUserId],
            ]);

        return reset($userList);
    }
}
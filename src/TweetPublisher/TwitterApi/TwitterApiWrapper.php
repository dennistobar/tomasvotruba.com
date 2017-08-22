<?php declare(strict_types=1);

namespace TomasVotruba\Website\TweetPublisher\TwitterApi;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use TomasVotruba\Website\TweetPublisher\TweetEntityCompleter;
use TwitterAPIExchange;

final class TwitterApiWrapper
{
    /**
     * @var string
     */
    private const API_VERSION = '1.1';

    /**
     * @var string
     */
    private const UPDATE_URL = 'https://api.twitter.com/' . self::API_VERSION . '/statuses/update.json';

    /**
     * @var string
     * @see https://dev.twitter.com/rest/reference/get/statuses/user_timeline
     */
    private const TIMELINE_URL = 'https://api.twitter.com/' . self::API_VERSION . '/statuses/user_timeline.json';

    /**
     * @var string
     */
    private $twitterName;

    /**
     * @var TwitterAPIExchange
     */
    private $twitterAPIExchange;

    /**
     * @var TweetEntityCompleter
     */
    private $tweetEntityCompleter;

    public function __construct(
        string $twitterName,
        TwitterAPIExchange $twitterAPIExchange,
        TweetEntityCompleter $tweetEntityCompleter
    ) {
        $this->twitterName = $twitterName;
        $this->twitterAPIExchange = $twitterAPIExchange;
        $this->tweetEntityCompleter = $tweetEntityCompleter;
    }

    /**
     * @return string[]
     */
    public function getPublishedTweets(): array
    {
        $rawTweets = $this->getPublishedTweetsRaw();

        $rawTweets = $this->tweetEntityCompleter->completeOriginalUrlsToText($rawTweets);

        $tweets = [];
        foreach ($rawTweets as $fullTweet) {
            $tweets[] = $fullTweet['text'];
        }

        return $tweets;
    }

    public function publishTweet(string $status): void
    {
        $this->callPost(self::UPDATE_URL, [
            'status' => $status
        ]);
    }

    public function getDaysSinceLastTweet(): int
    {
        $rawTweets = $this->getPublishedTweetsRaw();
        $lastRawTweet = array_pop($rawTweets);

        $tweetPublishDate = DateTime::from($lastRawTweet['created_at']);
        $dateDiff = $tweetPublishDate->diff(new DateTime('today'));

        return (int) $dateDiff->format('%a');
    }

    /**
     * @return mixed[]
     */
    private function getPublishedTweetsRaw(): array
    {
        return $this->callGet(self::TIMELINE_URL, '* from:' . $this->twitterName, [
            'count' => 70, // these will be filtered down by following conditions; at least number of posts
            'trim_user' => true, // we don't need any user info
            'exclude_replies' => true, // we don't need replies
            'include_rts' => false, // we don't need retweets
            'since_id' => 824225319879987203 // this started at 2017-08-20, nothing before
        ]);
    }

    /**
     * @param mixed[] $options
     * @return mixed[]
     */
    private function callGet(string $endPoint, string $query, array $options): array
    {
        $jsonResponse = $this->twitterAPIExchange->setGetfield(sprintf(
            '?q=%s&%s',
            $query,
            http_build_query($options)
        ))
            ->buildOauth($endPoint, 'GET')
            ->performRequest();

        return Json::decode($jsonResponse, Json::FORCE_ARRAY);
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     *
     */
    private function callPost(string $endPoint, array $data): array
    {
        $jsonResponse = $this->twitterAPIExchange->setPostfields($data)
            ->buildOauth($endPoint, 'POST')
            ->performRequest();

        return Json::decode($jsonResponse, Json::FORCE_ARRAY);
    }
}

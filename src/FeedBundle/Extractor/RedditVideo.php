<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class RedditVideo extends AbstractExtractor
{
    protected $redditVideoData = null;

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $host || false === $path) {
            return false;
        }

        if (!in_array($host, ['reddit.com', 'www.reddit.com'], true)) {
            return false;
        }

        $url = 'https://' . $host . $path . '/.json';
        $url = str_replace('//.json', '/.json', $url);

        try {
            $data = $this->client->get($url)->json();
        } catch (RequestException $e) {
            return false;
        }

        // we only match reddit video
        if (!isset($data[0]['data']['children'][0]['data'])
            || $data[0]['data']['children'][0]['data']['domain'] !== 'v.redd.it') {
            return false;
        }

        $this->redditVideoData = $data[0]['data']['children'][0]['data'];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->redditVideoData) {
            return '';
        }

        $thumbnail = $this->redditVideoData['thumbnail'];
        if (!empty($this->redditVideoData['preview']['images'][0]['source']['url'])) {
            $thumbnail = $this->redditVideoData['preview']['images'][0]['source']['url'];
        }

        return '<div><h2>' . $this->redditVideoData['title'] . '</h2>' .
            '<ul><li>Score: ' . $this->redditVideoData['score'] . '</li><li>Comments: ' . $this->redditVideoData['num_comments'] . '</li><li>Flair: ' . $this->redditVideoData['link_flair_text'] . '</li></ul>' .
            '<p><img src="' . $thumbnail . '"></p></div>' .
            '<iframe src="' . $this->redditVideoData['media']['reddit_video']['fallback_url'] . '" frameborder="0" scrolling="no" width="' . $this->redditVideoData['media']['reddit_video']['width'] . '" height="' . $this->redditVideoData['media']['reddit_video']['height'] . '" allowfullscreen></iframe>';
    }
}

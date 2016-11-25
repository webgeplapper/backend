<?php

namespace App\Http\Controllers;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Illuminate\Support\Facades\Cache;

class EpisodesController extends Controller
{
    const PATH_TO_EPISODES = '../../webgeplapper-episodes';

    private function limitToEpisodeFolders($elem)
    {
        // Hidden files will stay hidden
        if (starts_with($elem, '.')) {
            return false;
        }

        $dir = $this::PATH_TO_EPISODES . '/' . $elem;

        // Only directories are considered
        if (!is_dir($dir)) {
            return false;
        }

        return true;
    }

    private function readDescription($episodeFolder)
    {
        $descriptionPath = $this::PATH_TO_EPISODES . '/' . $episodeFolder . '/description.yaml';

        // The directory needs a valid description-file
        if (!file_exists($descriptionPath)) {
            return $contents = [ 'error' => [
                'type' => 'fileError',
                'msg' => 'description.yaml is missing'
            ]];
        }

        try {
            $contents = Yaml::parse(file_get_contents($descriptionPath));
        } catch (ParseException $e) {
            $contents = [ 'error' => [
              'type' => 'parseError',
              'msg' => $e->getMessage()
            ]];
        }

        return $contents;
    }

    private function readEpisodes()
    {
        // Is it cached?
        if (Cache::has('allEpisodes')) {
            return Cache::get('allEpisodes');
        }

        // Read all episodes
        $files = scandir($this::PATH_TO_EPISODES);

        // Limit to directories which are not in the blacklist
        $files = array_filter($files, [$this, 'limitToEpisodeFolders']);

        // Build the array of episodes
        $episodes = [];

        foreach ($files as $episodeFolder) {
            $description = $this::readDescription($episodeFolder);

            // TODO merge with default values for meta and stuff

            // Don't include hidden episodes
            if ($description['meta']['public'] === false) {
                continue;
            }

            $description['id'] = $episodeFolder;
            $episodes[] = $description;
        }

        // Cache for one minute
        Cache::put('allEpisodes', $episodes, 1);

        // Return them as long as they are fresh
        return $episodes;
    }

    private function readEpisode($id)
    {
        return array_first($this::readEpisodes(), function ($episode) use ($id) {
            return $episode['id'] === $id;
        });
    }

    public function index()
    {
        return response()->json([ 'episodes' => $this->readEpisodes() ]);
    }

    public function view($id)
    {
        $episode = $this::readEpisode($id);

        if (!$episode) {
            return abort(404);
        }

        return response()->json([ 'episode' => $episode ]);
    }
}

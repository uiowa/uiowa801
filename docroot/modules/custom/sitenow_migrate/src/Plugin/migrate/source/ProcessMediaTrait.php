<?php

namespace Drupal\sitenow_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\MigrateException;

/**
 * Provides functions for processing media in source plugins.
 */
trait ProcessMediaTrait {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Get the source path from the migration config.
   */
  protected function getSourceBasePath() {
    if (isset($this->configuration['constants']) && isset($this->configuration['constants']['SOURCE_BASE_PATH'])) {
      return $this->configuration['constants']['SOURCE_BASE_PATH'];
    }

    return '';
  }

  /**
   * Return the path we'll be writing to.
   */
  protected function getDrupalFileDirectory() {
    return 'public://' . date('Y-m') . '/';
  }

  /**
   * Regex to find Drupal 7 JSON for inline embedded files.
   */
  public function entityReplace($match) {
    $fid = $match[1];
    $file_data = $this->fidQuery($fid);
    if ($file_data) {
      $uuid = $this->getMid($file_data['filename'])['uuid'];
      return $this->constructInlineEntity($uuid);
    }
    // Failed to find a file, so let's leave the content unchanged.
    return $match;
  }

  /**
   * Simple query to get info on the Drupal 7 file based on fid.
   */
  public function fidQuery($fid) {
    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->condition('f.fid', $fid);
    $results = $query->execute();
    return $results->fetchAssoc();
  }

  /**
   * Fetch the media uuid based on the provided filename.
   */
  public function getMid($filename) {
    $connection = \Drupal::database();
    $query = $connection->select('file_managed', 'f');
    $query->join('media__field_media_image', 'fmi', 'f.fid = fmi.field_media_image_target_id');
    $query->join('media', 'm', 'fmi.entity_id = m.mid');
    return $query->fields('m', ['uuid', 'mid'])
      ->condition('f.filename', $filename)
      ->execute()
      ->fetchAssoc();
  }

  /**
   * Build the new inline embed entity format for Drupal 8 images.
   */
  public function constructInlineEntity($uuid) {
    $parts = [
      '<drupal-entity',
      'data-embed-button="media_entity_embed"',
      'data-entity-embed-display="view_mode:media.full"',
      'data-entity-embed-display-settings=""',
      'data-entity-type="media"',
      'data-entity-uuid="' . $uuid . '"',
      'data-langcode="en">',
      '</drupal-entity>',
    ];
    return implode(" ", $parts);
  }

  /**
   * Fetch the media id based on the original site's fid.
   */
  protected function getFid($original_fid, $migrate_map = 'migrate_map_d7_file') {
    $connection = \Drupal::database();
    $query = $connection->select($migrate_map, 'mm');
    $query->join('media__field_media_image', 'fmi', 'mm.destid1 = fmi.field_media_image_target_id');
    return $query->fields('fmi', ['entity_id'])
      ->condition('mm.sourceid1', $original_fid)
      ->execute()
      ->fetchField();
  }

  /**
   * Download a remote file to the destination file directory.
   *
   * @param string $filename
   *   Filename of the file to be downloaded.
   * @param string $source_base_path
   *   The base path for files at the source.
   * @param string $drupal_file_directory
   *   The base path for the file directory to place the downloaded file.
   *
   * @return int|bool
   *   Returns the fid of the new file record or FALSE if there is
   *   an issue.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function downloadFile($filename, $source_base_path, $drupal_file_directory) {
    // Suppressing errors, because we expect there to be at least some
    // private:// files or 404 errors.
    $raw_file = @file_get_contents($source_base_path . $filename);
    if (!$raw_file) {
      return FALSE;
    }

    // Prepare directory in case it doesn't already exist.
    $dir = $this->fileSystem
      ->dirname($drupal_file_directory . $filename);
    if (!$this->fileSystem
      ->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      // Something went seriously wrong.
      throw new MigrateException("Could not create or write to directory '{$dir}'");
    }

    // Try to write the file.
    $file = file_save_data($raw_file, $drupal_file_directory . $filename);

    // If we have a file, continue.
    if ($file) {
      // Get a connection for the destination database.
      $connection = \Drupal::database();
      $query = $connection->select('file_managed', 'f');
      return $query->fields('f', ['fid'])
        ->condition('f.filename', $filename)
        ->execute()
        ->fetchField();
    }

    return FALSE;
  }

  /**
   * Create a media entity for images.
   *
   * @param int $fid
   *   File id for the media being created.
   * @param array $meta
   *   Associative array holding the title and alt texts.
   * @param int $owner_id
   *   User id for the media owner, or default to anonymous.
   *
   * @return false|int|string|null
   *   Media id, if successful, or else false.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMediaEntity($fid, array $meta, $owner_id = 1) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);

    if ($file) {
      $fileType = explode('/', $file->getMimeType())[0];
      // Currently handles images and documents.
      // May need to check for other file types.
      switch ($fileType) {

        case 'image':
          /** @var \Drupal\Media\MediaInterface $media */
          $media = $this->entityTypeManager->getStorage('media')->create([
            'bundle' => 'image',
            'field_media_image' => [
              'target_id' => $fid,
              'alt' => $meta['alt'],
              'title' => $meta['title'],
            ],
            'langcode' => 'en',
          ]);

          // Need to truncate the title prior to setting the media name
          // due to media.name column schema restriction.
          if (strlen($meta['title']) > 255) {
            // Break at a word. Doesn't make a perfect title,
            // but preserves some of the original intention.
            $title = wordwrap($meta['title'], 255);
            $title = substr($title, 0, strpos($title, '\n'));
          }
          else {
            $title = $meta['title'];
          }
          $media->setName($title);
          $media->setOwnerId($owner_id);
          $media->save();
          return $media->id();

        case 'application':
        case 'document':
        case 'file':
          /** @var \Drupal\Media\MediaInterface $media */
          $media = $this->entityTypeManager->getStorage('media')->create([
            'bundle' => 'file',
            'field_media_file' => [
              'target_id' => $fid,
              'display' => 1,
              'description' => '',
            ],
            'langcode' => 'en',
            'metadata' => [],
          ]);

          $media->setName($file->getFileName());
          $media->setOwnerId($owner_id);
          $media->save();
          return $media->id();

        default:
          return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Process an image field.
   */
  protected function processImageField(&$row, $field_name) {
    // Check if an image was attached, and if so, update with new fid.
    $original_fid = $row->getSourceProperty("{$field_name}_fid");

    if (isset($original_fid)) {
      $uri = $this->fidQuery($original_fid)['uri'];
      $filename_w_subdir = str_replace('public://', '', $uri);
      // Split apart the filename from the subdirectory path.
      $filename_w_subdir = explode('/', $filename_w_subdir);
      $filename = array_pop($filename_w_subdir);
      $subdir = implode('/', $filename_w_subdir) . '/';
      // Get a connection for the destination database.
      $dest_connection = \Drupal::database();
      $dest_query = $dest_connection->select('file_managed', 'f');
      $new_fid = $dest_query->fields('f', ['fid'])
        ->condition('f.filename', $filename)
        ->execute()
        ->fetchField();

      $meta = [
        'alt' => $row->getSourceProperty("{$field_name}_alt"),
        'title' => $row->getSourceProperty("{$field_name}_title"),
      ];

      // If there's no fid in the D8 database,
      // then we'll need to fetch it from the source.
      if (!$new_fid) {
        // Use the filename, update the source base path with the subdirectory.
        $new_fid = $this->downloadFile($filename, $this->getSourceBasePath() . $subdir, $this->getDrupalFileDirectory());
        if ($new_fid) {
          $mid = $this->createMediaEntity($new_fid, $meta, 1);
        }
      }
      else {
        $mid = $this->getMid($filename)['mid'];
        // And in case we had the file, but not the media entity.
        if (!$mid) {
          $mid = $this->createMediaEntity($new_fid, $meta, 1);
        }
      }
      if ($mid) {
        $row->setSourceProperty("{$field_name}_fid", $mid);
      }
      else {
        // If we don't have a media ID at this point,
        // we need to unset the ID.
        $row->setSourceProperty("{$field_name}_fid", NULL);
      }
    }
  }

  /**
   * Replace inline image tags with media references.
   *
   * Used this as reference: https://stackoverflow.com/a/3195048.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   */
  protected function replaceInlineImages($content, $stub) {
    $drupal_file_directory = $this->getDrupalFileDirectory();

    // Create a HTML content fragment.
    $document = Html::load($content);

    // Get all the image from the $content.
    $images = $document->getElementsByTagName('img');

    // As we replace the inline images, they are actually
    // removed in the DOMNodeList $images, so we have to
    // use a regressive loop to count through them.
    // See https://www.php.net/manual/en/domnode.replacechild.php#50500.
    $i = $images->length - 1;

    while ($i >= 0) {
      // The current inline image element.
      $img = $images->item($i);
      $src = $img->getAttribute('src');
      // No point in continuing after this point because the
      // image is broken if we don't have a 'src'.
      if ($src) {
        // Process the 'src' into a consistent format.
        // Get the filepath and filename separated,
        // and fix any spaces in the URL prior to trying to download.
        $file_path = str_replace(' ', '%20', rawurldecode($src));
        $filename = basename($file_path);

        // If it's an external image, don't touch it
        // and continue on to the next iteration.
        if (!str_contains($file_path, $stub)) {
          $i--;
          continue;
        }
        // Attempt to get existing image.
        $fid = $this->getD8FileByFilename($filename);

        if (!$fid) {
          // Get the prefix to the path for downloading purposes.
          // Also remove URL front, in case absolute URLs to same site
          // were used.
          $prefix_path = explode($stub, $file_path);
          $prefix_path = array_pop($prefix_path);
          // And take out the filename.
          $prefix_path = str_replace($filename, '', $prefix_path);

          // Download the file and create the file record.
          $fid = $this->downloadFile($filename, $this->getSourceBasePath() . $prefix_path, $drupal_file_directory);

          // Get meta data an create the media entity.
          $meta = [];
          foreach (['alt', 'title'] as $name) {
            if ($prop = $img->getAttribute($name)) {
              $meta[$name] = $prop;
            }
          }
          // If we successfully downloaded the file, create the media entity.
          if ($fid) {
            $this->createMediaEntity($fid, $meta);
          }
        }

        // Get the media UUID.
        $uuid = $this->getMid($filename)['uuid'];

        // There is an issue at this point if we don't have an MID,
        // and we definitely don't want to replace the existing item
        // with a broken media embed.
        if ($uuid) {
          // Create the <drupal-media> element.
          $media_embed = $document->createElement('drupal-media');
          $media_embed->setAttribute('data-entity-uuid', $uuid);
          // @todo Determine how to correctly set the crop.
          //   $media_embed->setAttribute('data-view-mode', 'full_no_crop');
          $media_embed->setAttribute('data-entity-type', 'media');

          // Set the alignment if we can determine it.
          $align = $this->getImageAlign($img);
          if ($align) {
            $media_embed->setAttribute('data-align', $align);
          }

          // Replace the <img> element with the <drupal-media> element.
          $img->parentNode->replaceChild($media_embed, $img);
        }
        // If we weren't able to find or download an image,
        // let's insert a token for cleanup later.
        else {
          $token = $document->createComment('Missing image: ' . $file_path);
          // Replace the <img> element with our token comment.
          $img->parentNode->replaceChild($token, $img);
        }
      }

      $i--;
    }

    // Convert back into a string and return it.
    return Html::serialize($document);
  }

  /**
   * Attempt to determine the image alignment.
   */
  protected function getImageAlign($img) {
    $align = NULL;
    if ($img->getAttribute('align')) {
      $align = $img->getAttribute('align');
    }
    elseif ($img->getAttribute('style')) {
      preg_match('/(?:float: )(left|right)/i', $img->getAttribute('style'), $align_match);
      if ($align_match && !empty($align_match)) {
        $align = $align_match[1];
      }
    }

    return $align;
  }

  /**
   * Get the D7 file record using the filename.
   */
  protected function getD8FileByFilename($filename) {
    $connection = \Drupal::database();
    $query = $connection->select('file_managed', 'f');
    return $query->fields('f', ['fid'])
      ->condition('f.filename', $filename)
      ->execute()
      ->fetchField();
  }

}

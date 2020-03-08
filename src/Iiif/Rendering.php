<?php

/*
 * Copyright 2020 Daniel Berthereau
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software’s author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user’s attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software’s suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace IiifServer\Iiif;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\MediaRepresentation;

/**
 *@link https://iiif.io/api/presentation/3.0/#rendering
 */
class Rendering extends AbstractResourceType
{
    protected $type = null;

    protected $keys = [
        'id' => self::REQUIRED,
        'type' => self::REQUIRED,
        'label' => self::OPTIONAL,
        'format' => self::OPTIONAL,
    ];

    /**
     * The rendering types are efined by the resource class, by the media type.
     *
     * Note: the resource class of the item is not used.
     *
     * @todo Rendering type It's not clealy specified, except in the context. All dctype or not? Other than dctype? Default?
     *
     * @link https://iiif.io/api/image/3/context.json
     */
    protected $renderingTypes = [
        'dctype:Dataset' => 'Dataset',
        'dctype:StillImage' => 'Image',
        'dctype:MovingImage' => 'Video',
        'dctype:Sound' => 'Audio',
        'dctype:Text' => 'Text',
    ];

    protected $mediaTypeTypes = [
        // 'application',
        'audio' => 'Audio',
        // 'example',
        // 'font',
        'image' => 'Image',
        // 'message',
        // 'model',
        // 'multipart',
        'text' => 'Text',
        'video' => 'Video',
    ];

    /**
     * Some common media-types.
     *
     * @var array
     */
    protected $mediaTypes = [
        // @see \Omeka\Form\SettingForm::MEDIA_TYPE_WHITELIST
        'application/msword' => 'Text',
        'application/ogg' => 'Video',
        'application/pdf' => 'Text',
        'application/rtf' => 'Text',
        'application/vnd.ms-access' => 'Dataset',
        'application/vnd.ms-excel' => 'Dataset',
        'application/vnd.ms-powerpoint' => 'Text',
        'application/vnd.ms-project' => 'Dataset',
        'application/vnd.ms-write' => 'Text',
        'application/vnd.oasis.opendocument.chart' => 'Image',
        'application/vnd.oasis.opendocument.database' => 'Dataset',
        'application/vnd.oasis.opendocument.formula' => 'Text',
        'application/vnd.oasis.opendocument.graphics' => 'Image',
        'application/vnd.oasis.opendocument.presentation' => 'Text',
        'application/vnd.oasis.opendocument.spreadsheet' => 'Dataset',
        'application/vnd.oasis.opendocument.text' => 'Text',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Text',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'Text',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Dataset',
        'application/x-gzip' => null,
        'application/x-ms-wmp' => null,
        'application/x-msdownload' => null,
        'application/x-shockwave-flash' => null,
        'application/x-tar' => null,
        'application/zip' => null,
        'application/xml' => 'Text',
        // @see \Next\File\TempFile::xmlMediaTypes
        'application/vnd.recordare.musicxml' => 'Text',
        'application/vnd.mei+xml' => 'Text',
    ];

    protected $rendererTypes = [
        'file' => null,
        'oembed' => 'Text',
        'youtube' => 'Video',
        'html' => 'Text',
        'iiif' => 'Image',
        'tile' => 'Image',
    ];

    public function __construct(AbstractResourceEntityRepresentation $resource, array $options = null)
    {
        if (!($resource instanceof MediaRepresentation)) {
            throw new \RuntimeException(
                'A media is required to build a canvas.'
            );
        }

        parent::__construct($resource, $options);

        // Prepare type one time.
        $this->getType();
    }

    public function getId()
    {
        if (!array_key_exists('id', $this->_storage)) {
            // FIXME Manage all media Omeka types (Iiif, youtube, etc.)..
            $url = $this->resource->originalUrl();
            if ($url) {
                $id = $url;
            } else {
                $siteSlug = @$this->options['siteSlug'];
                if ($siteSlug) {
                    // TODO Return media page or item page? Add an option.
                    $id = $this->resource->siteUrl($siteSlug, true);
                } else {
                    $id = null;
                }
            }
            $this->_storage['id'] = $id;
        }

        return $this->_storage['id'];
    }

    public function getType()
    {
        $mediaType = $this->resource->mediaType();
        if ($mediaType) {
            $mediaTypeType = strtok($mediaType, '/');
            if (isset($this->mediaTypeTypes[$mediaTypeType])) {
                $this->type = $this->mediaTypeTypes[$mediaTypeType];
                return $this->type;
            }
        }

        // Managed some other common media types.
        if (isset($this->mediaTypes[$mediaType])) {
            $this->type = $this->mediaTypes[$mediaType];
            return $this->type;
        }

        $renderer = $this->resource->renderer();
        if (isset($this->rendererTypes[$renderer])) {
            $this->type = $this->rendererTypes[$renderer];
            return $this->type;
        }

        /* These cases are normally managed by the media type above.
        // $extension = $this->resource->extension();
        if ($renderer === 'file') {
            // See \Omeka\Media\Renderer::render()
            // $fileRenderers = $this->resource->getServiceLocator()->get('Config')['file_renderers'] + ['factories' => []];
            /** @var \Omeka\Media\FileRenderer\Manager $fileRendererManager
            // $fileRendererManager = $this->resource->getServiceLocator()->get('Omeka\Media\FileRenderer\Manager');
        }
        */

        return null;
    }

    /**
     * The label is not a title, but an info about the type, since the main
     * label is already known.
     *
     * {@inheritDoc}
     * @see \IiifServer\Iiif\AbstractResourceType::getLabel()
     */
    public function getLabel()
    {
        if (!$this->type) {
            return null;
        }

        $format = $this->getFormat();
        $label = $format
            ? sprintf('%1$s [%2$s]', $this->type, $format())
            : $format;
        return new ValueLanguage(['none' => $label]);
    }

    /**
     * Get the media type of the resource.
     *
     * @todo Manage the format of non-file resources (iiif, oembed, etc.).
     *
     * @return string|null
     */
    public function getFormat()
    {
        $mediaType = $this->resource->mediaType();
        if ($mediaType) {
            return $mediaType;
        }
        return null;
    }
}

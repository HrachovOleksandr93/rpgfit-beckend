<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for viewing and managing MediaFile entities.
 *
 * Allows admins to browse uploaded media files, filter by entity type,
 * and view upload details. Files are uploaded via the API, not the admin panel.
 *
 * Note: storagePath stores just the filename (e.g. "abc123.png").
 * The full disk path is: public/uploads/{entityType}/{storagePath}.
 * Original files are served by nginx; resized variants by LiipImagineBundle.
 *
 * @extends AbstractAdmin<\App\Domain\Media\Entity\MediaFile>
 */
class MediaFileAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('originalFilename')
            ->add('entityType')
            ->add('entityId')
            ->add('mimeType')
            ->add('fileSize')
            ->add('uploadedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('entityType')
            ->add('mimeType')
            ->add('originalFilename');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('originalFilename', TextType::class, ['disabled' => true])
            ->add('storagePath', TextType::class, ['disabled' => true])
            ->add('mimeType', TextType::class, ['disabled' => true])
            ->add('entityType', TextType::class)
            ->add('entityId', TextType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('originalFilename')
            ->add('storagePath')
            ->add('mimeType')
            ->add('fileSize')
            ->add('entityType')
            ->add('entityId')
            ->add('uploadedAt');
    }
}

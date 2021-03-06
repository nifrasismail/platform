<?php

namespace Oro\Bundle\ReminderBundle\Form\Type;

use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MethodType extends AbstractType
{
    /**
     * @var SendProcessorRegistry
     */
    protected $sendProcessorRegistry;

    /**
     * @param SendProcessorRegistry $sendProcessorRegistry
     */
    public function __construct(SendProcessorRegistry $sendProcessorRegistry)
    {
        $this->sendProcessorRegistry = $sendProcessorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices'  => $this->sendProcessorRegistry->getProcessorLabels(),
                'expanded' => false,
                'multiple' => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_reminder_method';
    }
}

<?php

namespace MauticPlugin\RetailMarketingBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Exception\SchemaException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;

/**
 * Class for listening webhook from facebook.
 */
class PublicController extends CommonController
{
    /**
     * Webhook listener for this.
     */
    public function webhookPayloadAction(Request $request): Response
    {
        $data = [];
        $mautic_data = [
            'email',
            'firstname',
            'lastname',
            'tags'
        ];
        foreach ($mautic_data as $mautic_field) {
            $data[$mautic_field] = $request->get($mautic_field);
        }

        //initialize the lead model
        $leadModel = $this->getModel('lead');
        // stuff before...
        $lead = new Lead();
        $multi_select_field = [
            'tags'
        ];
        $email = $data['email'];

        $leads = $leadModel->getRepository()->getEntities([
            'filter' => [
                'where' => [
                    [
                        'column' => 'l.email',
                        'expr' => 'eq',
                        'val'  => $email
                    ]
                ],
            ],
            'ignore_paginator' => true
        ]);

        if (count($leads)) {
            $lead = $leads[array_keys($leads)[0]];
        }

        foreach ($data as $formField => $formValue) {
            if (in_array($formField, $multi_select_field)) {
                $tag_entity = new Tag($formValue);
                $lead->addTag($tag_entity);
            }
            else {
                $lead->addUpdatedField($formField, $formValue, null);
            }

        }

        $flag = true;
        try {
            $leadModel->getRepository()->saveEntity($lead);
        } catch (SchemaException $e) {
            $flag = false;
        }

        if (false === $flag) {
            $responseMessage = 'No contact created/updated';
            $responseType    = Response::HTTP_NO_CONTENT;
        } else {
            $responseMessage = 'Created';
            $responseType    = Response::HTTP_OK;
        }
        return new JsonResponse(['status' => $responseMessage], $responseType);
    }
}

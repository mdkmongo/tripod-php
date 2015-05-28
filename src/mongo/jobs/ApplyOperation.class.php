<?php

namespace Tripod\Mongo\Jobs;

/**
 * Class ApplyOperation
 * @package Tripod\Mongo\Jobs
 */
class ApplyOperation extends JobBase {
    /**
     * Run the ApplyOperation job
     * @throws \Exception
     */
    public function perform()
    {
        try
        {
            $this->debugLog("ApplyOperation::perform() start");

            $timer = new \Tripod\Timer();
            $timer->start();

            $this->validateArgs();

            // set the config to what is received
            \Tripod\Mongo\Config::setConfig($this->args["tripodConfig"]);

            $subject = $this->createImpactedSubject($this->args['subject']);

            $subject->update();

            $timer->stop();
            // stat time taken to process item, from time it was created (queued)
            $this->getStat()->timer(MONGO_QUEUE_APPLY_OPERATION_SUCCESS,$timer->result());

            $this->debugLog("ApplyOperation::perform() done in {$timer->result()}ms");
        }
        catch (\Exception $e)
        {
            $this->errorLog("Caught exception in ".get_class($this).": ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * For mocking
     * @param array $args
     * @return \Tripod\Mongo\ImpactedSubject
     */
    protected function createImpactedSubject(array $args)
    {
        return new \Tripod\Mongo\ImpactedSubject(
            $args["resourceId"],
            $args["operation"],
            $args["storeName"],
            $args["podName"],
            $args["specTypes"]
        );        
    }

    /**
     * Validate args for ApplyOperation
     * @return array
     */
    protected function getMandatoryArgs()
    {
        return array("tripodConfig","subject");
    }
}

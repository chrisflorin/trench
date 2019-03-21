<?php

use Trench\Models\AbstractModel;

class AbstractArraySeeder extends AbstractSeeder
{
    /** @var array $itemList */
    protected $itemList = [];

    /** @var AbstractModel $model */
    protected $model;

    //Override this function to do something when the seeder fails to complete
    protected function fail()
    {

    }

    //Override this function to process each model after creation
    protected function process(AbstractModel $model)
    {
        return true;
    }

    /**
     * @throws Throwable
     */
    public function run()
    {
        try {
            $result = $this->model->getConnection()->transaction(function () {
                foreach ($this->itemList as $item) {
                    $model = $this->model->create($item);

                    $result = $this->process($model);

                    if ($result === false) {
                        return false;
                    }
                }

                return true;
            });

            $result ? $this->success() : $this->fail();
        } catch (Throwable $e) {
            $this->fail();

            throw $e;
        }
    }

    //Override this function to do something when the seeder is finished and successful
    protected function success()
    {

    }
}

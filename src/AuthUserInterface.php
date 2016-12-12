<?php
namespace Germania\Authentication;

interface AuthUserInterface
{

    /**
     * Returns the User ID.
     * @return mixed
     */
    public function getId();

    /**
     * Sets the User ID.
     * @param mixed $id
     */
    public function setId( $id );
}

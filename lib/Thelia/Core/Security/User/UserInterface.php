<?php

namespace Thelia\Core\Security\User;

/**
 * This interface should be implemented by user classes
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 *
 */
interface UserInterface {

    /**
     * Return the user unique name
     */
    public function getUsername();

    /**
     * Return the user encoded password
     */
    public function getPassword();

    /**
     * Check a string against a the user password
     */
    public function checkPassword($password);

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * @return Role[] The user roles
     */
    public function getRoles();

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     *
     * @return void
     */
    public function eraseCredentials();
}
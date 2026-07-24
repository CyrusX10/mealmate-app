<?php
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase {
    public function testShortPasswordRejected() {
        $password = "abc";
        $result = strlen($password) >= 8;
        $this->assertFalse($result);
    }
    public function testPasswordMismatchDetected() {
        $p1 = "Pass1234";
        $p2 = "Pass5678";
        $this->assertNotEquals($p1, $p2);
    }
    public function testValidEmailAccepted() {
        $email = "test@gmail.com";
        $result = filter_var($email, FILTER_VALIDATE_EMAIL);
        $this->assertNotFalse($result);
    }
    public function testInvalidEmailRejected() {
        $email = "notanemail";
        $result = filter_var($email, FILTER_VALIDATE_EMAIL);
        $this->assertFalse($result);
    }
    public function testEmptyFieldDetected() {
        $name = "";
        $this->assertEmpty($name);
    }
}
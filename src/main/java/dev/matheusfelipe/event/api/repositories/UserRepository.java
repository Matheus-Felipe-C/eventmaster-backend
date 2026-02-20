package dev.matheusfelipe.event.api.repositories;

import java.util.Optional;

import org.springframework.data.jpa.repository.JpaRepository;

import dev.matheusfelipe.event.api.user.User;

public interface UserRepository extends JpaRepository<User, Long>{
    Optional<User> findByEmail(String email);
}

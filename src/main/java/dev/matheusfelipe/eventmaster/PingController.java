package dev.matheusfelipe.eventmaster;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.ResponseStatus;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.http.HttpStatus;

@RestController
@RequestMapping("/")
public class PingController {
    
    @GetMapping
    @ResponseStatus(HttpStatus.OK)
    public void ping() {
        System.out.println("Tudo funcionando!");
    }
}

package com.example.smarthire.services;

import java.util.List;
import java.sql.SQLException;

public interface IService<T> {
    void add(T t) throws SQLException;
    void update(T t) throws SQLException;
    void delete(int id) throws SQLException;
    T getOne(int id) throws SQLException;
    List<T> getAll() throws SQLException;
}
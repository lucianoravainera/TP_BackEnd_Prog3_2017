
create database Estacionamiento_2017;
use Estacionamiento_2017;

#ALTER TABLE Estacionamiento_2017.usuarios AUTO_INCREMENT = 1

create table Estacionamiento_2017.usuarios (
    ID int not null auto_increment,
    nombre varchar(255) not null,
    apellido varchar(255) not null,
    sexo boolean not null,
    turno varchar(16) not null,
    perfil varchar(32) not null DEFAULT 'usuario',
    email varchar(255) not null,
    password varchar(255) not null,
    habilitado boolean not null default 1,
    foto varchar(512),
    fecha_creado DATETIME DEFAULT CURRENT_TIMESTAMP,

    unique (email),
    primary key (ID)
);

create table Estacionamiento_2017.operaciones (
    ID int not null auto_increment,
    ingreso_usuarioID int not null,
    salida_usuarioID int,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_salida DATETIME,
    importe decimal(15, 2) default 0,
    discapacitados int,
    
    color varchar(255),
    patente varchar(32) not null,
    marca varchar(255),
    cochera int not null,
    foto varchar(255),

    primary key (ID),
    FOREIGN KEY (ingreso_usuarioID) REFERENCES usuarios(ID),
    FOREIGN KEY (salida_usuarioID) REFERENCES usuarios(ID)
);

create table Estacionamiento_2017.ingresos (
    ID int not null auto_increment,
    ingreso_usuarioID int not null,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,

    primary key (ID),
    FOREIGN KEY (ingreso_usuarioID) REFERENCES usuarios(ID)
);

#ALTER TABLE Estacionamiento_2017.usuarios AUTO_INCREMENT = 1;

insert into Estacionamiento_2017.usuarios (nombre, apellido, sexo, turno, perfil, email, password, habilitado, fecha_creado) 
values ('Administrador', 'Admin', 0, 'T', 'admin', 'admin@utn.com', '1234', 1, NOW());

insert into Estacionamiento_2017.usuarios (nombre, apellido, sexo, turno, perfil, email, password, habilitado, fecha_creado) 
values ('Usuario', 'Prueba', 0, 'T', 'usuario', 'usuario@utn.com', '1234', 1, NOW());

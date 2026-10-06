<?php

namespace App\Enums;

/**
 * Rubro (業種). Sigue la clasificación industrial japonesa que usa el legacy,
 * donde se guarda como texto; aquí va como smallint según el estándar §4.
 *
 * Se usa en dos sitios distintos del cliente: el rubro de su lugar de trabajo
 * y el rubro propio cuando el cliente es una empresa.
 */
enum Industry: int
{
    case Agricultura = 1;          // 農業
    case Silvicultura = 2;         // 林業
    case Pesca = 3;                // 漁業
    case Mineria = 4;              // 鉱業
    case Canteras = 5;             // 採石業
    case ExtraccionGrava = 6;      // 砂利採取業
    case Construccion = 7;         // 建設業
    case Manufactura = 8;          // 製造業
    case Suministros = 9;          // 電気・ガス・熱供給・水道業
    case Telecomunicaciones = 10;  // 情報通信業
    case Transporte = 11;          // 運輸業
    case Correos = 12;             // 郵便業
    case ComercioMayorista = 13;   // 卸売業
    case ComercioMinorista = 14;   // 小売業
    case Finanzas = 15;            // 金融業
    case Seguros = 16;             // 保険業
    case Inmobiliaria = 17;        // 不動産業
    case AlquilerBienes = 18;      // 物品賃貸業
    case Investigacion = 19;       // 学術研究
    case ServiciosTecnicos = 20;   // 専門・技術サービス業
    case Alojamiento = 21;         // 宿泊業
    case Restauracion = 22;        // 飲食サービス業
    case ServiciosPersonales = 23; // 生活関連サービス業
    case Ocio = 24;                // 娯楽業
    case Educacion = 25;           // 教育
    case ApoyoEducativo = 26;      // 学習支援業
    case Salud = 27;               // 医療
    case Bienestar = 28;           // 福祉
    case OtrasLucrativas = 29;     // 他の営利事業
    case Otros = 30;               // その他（政治・経済・文化・宗教団体など）

    public function label(): string
    {
        return match ($this) {
            self::Agricultura => 'Agricultura',
            self::Silvicultura => 'Silvicultura',
            self::Pesca => 'Pesca',
            self::Mineria => 'Minería',
            self::Canteras => 'Canteras',
            self::ExtraccionGrava => 'Extracción de grava',
            self::Construccion => 'Construcción',
            self::Manufactura => 'Manufactura',
            self::Suministros => 'Electricidad, gas, calefacción y agua',
            self::Telecomunicaciones => 'Información y telecomunicaciones',
            self::Transporte => 'Transporte',
            self::Correos => 'Correos',
            self::ComercioMayorista => 'Comercio mayorista',
            self::ComercioMinorista => 'Comercio minorista',
            self::Finanzas => 'Finanzas',
            self::Seguros => 'Seguros',
            self::Inmobiliaria => 'Inmobiliaria',
            self::AlquilerBienes => 'Alquiler de bienes',
            self::Investigacion => 'Investigación académica',
            self::ServiciosTecnicos => 'Servicios profesionales y técnicos',
            self::Alojamiento => 'Alojamiento',
            self::Restauracion => 'Restauración',
            self::ServiciosPersonales => 'Servicios personales',
            self::Ocio => 'Ocio y entretenimiento',
            self::Educacion => 'Educación',
            self::ApoyoEducativo => 'Apoyo educativo',
            self::Salud => 'Salud',
            self::Bienestar => 'Bienestar social',
            self::OtrasLucrativas => 'Otras actividades lucrativas',
            self::Otros => 'Otros (asociaciones, entidades culturales o religiosas…)',
        };
    }
}
